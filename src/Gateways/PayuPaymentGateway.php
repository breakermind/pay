<?php

namespace Pay\Gateways;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use Pay\PaymentGateway;
use Pay\Models\Payment;
use Pay\Events\PaymentCreated;
use Pay\Events\PaymentCanceled;
use Pay\Events\PaymentConfirmed;
use Pay\Events\PaymentRefunded;
use Pay\Events\PaymentInformed;
use Pay\Http\Payu\OpenPayU_Refunds;
use OpenPayU_Configuration;
use OpenPayU_Order;
use OpenPayU_Refund;
use OpenPayU_Retrieve;
use OauthCacheFile;
use OpenPayU_Util;

/**
 * PayU payment gateway
 */
class PayuPaymentGateway implements PaymentGateway
{
	public $currency = 'PLN';

	public $allowed_ip = ['185.68.12.10', '185.68.12.11', '185.68.12.12', '185.68.12.26', '185.68.12.27', '185.68.12.28', '185.68.14.10', '185.68.14.11', '185.68.14.12', '185.68.14.26', '185.68.14.27', '185.68.14.28'];

	public $status = ['NEW', 'PENDING', 'WAITING_FOR_CONFIRMATION', 'CANCELED', 'COMPLETED', 'REJECTED', 'FAILED', 'REFUNDED'];

	function __construct()
	{
		$this->env = config('pay.payu.env') ?? 'sandbox';
		$this->pos_id = config('pay.payu.pos_id') ?? null;
		$this->pos_md5 = config('pay.payu.pos_md5') ?? null;
		$this->client_id = config('pay.payu.client_id') ?? null;
		$this->client_secret = config('pay.payu.client_secret') ?? null;
		$this->currency = config('pay.payu.currency') ?? 'PLN';

		// Create dir
		if (!is_dir(storage_path() . '/framework/pay/payu')) {
			@mkdir(storage_path() . '/framework/pay/payu', 0770);
		}

		// Config
		$this->config();
	}

	function config()
	{
		try {
			// Cache
			OpenPayU_Configuration::setOauthTokenCache(new OauthCacheFile(storage_path() . '/framework/pay/payu'));

			// Production Environment
			OpenPayU_Configuration::setEnvironment($this->env);

			if (!empty($this->pos_id)) {
				// POS ID and Second MD5 Key (from merchant admin panel)
				OpenPayU_Configuration::setMerchantPosId($this->pos_id);
				OpenPayU_Configuration::setSignatureKey($this->pos_md5);
			}

			if (!empty($this->client_id)) {
				// Oauth Client Id and Oauth Client Secret (from merchant admin panel)
				OpenPayU_Configuration::setOauthClientId($this->client_id);
				OpenPayU_Configuration::setOauthClientSecret($this->client_secret);
			}
		} catch (Exception $e) {
			report($e);
			throw new Exception('Payment api config error', 422);
		}
	}

	function pay(Order $order): string
	{
		$url = '';

		try {
			// Client address
			$client = $order->client;
			$total = $this->toCents($order->cost);
			$desc = 'ID-' . $order->uid;
			// Credentials
			$o['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
			// Urls
			$o['notifyUrl'] = $this->notifyUrl();
			$o['continueUrl'] = $this->successUrl($order);
			// Order uid string
			$o['extOrderId'] = $order->uid;
			$o['currencyCode'] = $this->currency;
			$o['customerIp'] = $this->ipAddress();
			$o['totalAmount'] = $total;
			$o['description'] = $desc;
			// Products
			$o['products'][0]['name'] = $desc;
			$o['products'][0]['unitPrice'] = $total;
			$o['products'][0]['quantity'] = 1;
			// Buyer
			$o['buyer']['email'] = $client->email;
			$o['buyer']['phone'] = $client->mobile;
			$o['buyer']['firstName'] = $client->name;
			$o['buyer']['lastName'] = $client->lastname ?? $client->name;
			$o['buyer']['language'] = $this->lang();

			// Create payu order
			$res = OpenPayU_Order::create($o);

			if ($res->getStatus() == 'SUCCESS') {
				$pid = $res->getResponse()->orderId;
				$url = $res->getResponse()->redirectUri;

				if (!empty($pid) && !empty($url)) {
					Payment::updateOrCreate([
						'id' => $pid,
						'order_uid' => $order->uid
					], [
						'total' => $total,
						'currency' => $this->currency,
						'gateway' => 'payu',
						'url' => $url,
						'ip' => request()->ip()
					]);

					// Emit event
					PaymentCreated::dispatch($order);

					return $url;
				} else {
					throw new Exception('Response: ' . $res->getResponse());
				}
			} else {
				throw new Exception('Status: ' . $res->getStatus());
			}
		} catch (Exception $e) {
			Log::error('PAYU_PAY_ERR ' . $e->getMessage());

			// Get payment url if exists in database
			$p = Payment::where(['order_uid' => $order->uid])->first();
			if (!empty($p->url)) {
				return $p->url;
			}

			throw new Exception('Payment api error', 422);
		}
	}

	function notify()
	{
		$body = request()->getContent();
		$data = trim($body);

		Log::error('PAYU_NOTIFY_BODY ' . $data . ' PAYU_NOTIFY_BODY_END');

		try {
			if (!in_array($this->ipAddress(), $this->allowed_ip)) {
				throw new Exception('PAYU_NOTIFY_INVALID_IP ' . $this->ipAddress());
			}

			if (empty($data)) {
				throw new Exception('PAYU_NOTIFY_INVALID_DATA');
			}

			$res = OpenPayU_Order::consumeNotification($data);

			// Emit event
			PaymentInformed::dispatch($res);

			// Order notify
			if (!empty($res->getResponse()->order)) {
				// Ids
				$notifyOrderId = $res->getResponse()->order->orderId;
				$notifyOrderStatus = $res->getResponse()->order->status;

				// Check if order exists in payu service
				$res = OpenPayU_Order::retrieve($notifyOrderId);

				if ($res->getStatus() == 'SUCCESS') {
					$payu_order = $res->getResponse()->orders[0];

					if ($payu_order) {
						$status = strtoupper($payu_order->status); // Payment status
						$order_id = $payu_order->extOrderId; // Shop uid
						$payment_id = $payu_order->orderId; // Payu uid
						$amount = $payu_order->totalAmount;
						$currency = $payu_order->currencyCode;

						$shop_order = Payment::where([
							'id' => $payment_id,
							'order_uid' => $order_id,
						])->first();

						if ($shop_order) {
							if (in_array($status, $this->status)) {
								$current_status = strtoupper($shop_order->status);
								if ($current_status != 'COMPLETED') {
									$shop_order->status = $status;
									$shop_order->currency = $currency ?? 'PLN';
									$shop_order->total = (int) $amount ?? 0;
									$shop_order->save();
								}
							} else {
								throw new Exception('PAYU_NOTIFY_INVALID_ORDER_STATUS');
							}

							return response('CONFIRMED', 200);
						}
					}
				}
			}

			// Refunds notify
			if (
				!empty($res->getResponse()->refund->refundId)
				&& !empty($res->getResponse()->extOrderId)
				&& !empty($res->getResponse()->orderId)
			) {
				$shop_order = Payment::where([
					'id' => $res->getResponse()->orderId,
					'order_uid' => $res->getResponse()->extOrderId,
				])->first();

				if ($shop_order) {
					$payu_status = $res->getResponse()->refund->status;

					if ($payu_status == 'FINALIZED' || $payu_status == 'CANCELED') {
						if ($shop_order->status_refund != 'FINALIZED') {
							$shop_order->status_refund = strtoupper($payu_status);
							$shop_order->save();
						}
					} else {
						throw new Exception('PAYU_NOTIFY_INVALID_REFUND_STATUS');
					}

					return response('OK', 200);
				}
			}
		} catch (Exception $e) {
			Log::error('PAYU_NOTIFY_ERR ' . $e->getMessage());
			// Show error in payu panel
			return response($e->getMessage(), 422);
		}

		// Order not found
		return response('PAYU_NOTIFY_INVALID_ORDER', 422);
	}

	function confirm(Order $order): string
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid,
				'status' => 'WAITING_FOR_CONFIRMATION'
			])->first();

			if ($p) {
				$res = OpenPayU_Order::statusUpdate([
					"orderId" => $p->id,
					"orderStatus" => 'COMPLETED'
				]);

				if ($res->getStatus() == 'SUCCESS') {
					$p->status = 'COMPLETED';
					$p->save();

					// Emit event
					PaymentConfirmed::dispatch($order);

					return 'COMPLETED';
				} else {
					throw new Exception('Status update error');
				}
			} else {
				throw new Exception('Invalid payment id');
			}
		} catch (Exception $e) {
			Log::error('PAYU_CONFIRM_ERR ' . $e->getMessage());
		}

		return 'ERROR';
	}

	function cancel(Order $order): string
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p) {
				$res = OpenPayU_Order::cancel($p->id);

				if ($res->getStatus() == 'SUCCESS') {
					$p->status = 'CANCELED';
					$p->save();

					// Emit event
					PaymentCanceled::dispatch($order);

					return 'CANCELED';
				} else {
					throw new Exception('Status update error');
				}
			} else {
				throw new Exception('Invalid payment id');
			}
		} catch (Exception $e) {
			Log::error('PAYU_CANCEL_ERR ' . $e->getMessage());
		}

		return 'ERROR';
	}

	function refund(Order $order): string
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p) {
				$res = OpenPayU_Refund::create($p->id, __('Refunding'), null);

				if ($res->getStatus() == 'SUCCESS') {
					if ($res->getResponse()->refund->status == 'PENDING') {
						$p->status_refund = 'PENDING';
						$p->save();

						// Emit event
						PaymentRefunded::dispatch($order);

						return 'PENDING';
					}
				} else {
					throw new Exception('Order has not been refunded');
				}
			} else {
				throw new Exception('Invalid payment id');
			}
		} catch (Exception $e) {
			Log::error('PAYU_REFUND_ERR ' . $e->getMessage());
		}

		return 'ERROR';
	}

	function refunds(Order $order)
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p) {
				$res = OpenPayU_Refunds::retrive($p->id);

				return $res->getResponse();
			} else {
				throw new Exception('Invalid payment id');
			}
		} catch (Exception $e) {
			Log::error('PAYU_REFUNDS_ERR ' . $e->getMessage());
		}

		return 'ERROR';
	}

	function refresh(Order $order): string
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p) {
				$res = OpenPayU_Order::retrieve($p->id);

				if ($res->getStatus() == 'SUCCESS') {
					$payu_order = $res->getResponse()->orders[0];
					if ($payu_order) {
						if (in_array($payu_order->status, $this->status)) {
							$p->status = strtoupper($payu_order->status);
							$p->save();

							// Return order status
							return $p->status;
						} else {
							throw new Exception('Invalid order status');
						}
					} else {
						throw new Exception('Order does not exists');
					}
				} else {
					throw new Exception('Invalid status');
				}
			} else {
				throw new Exception('Invalid id');
			}
		} catch (Exception $e) {
			Log::error('PAYU_REFRESH_ERR ' . $e->getMessage());
		}

		return 'ERROR';
	}

	function retrive(Order $order)
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p) {
				$res = OpenPayU_Order::retrieve($p->id);

				if ($res->getStatus() == 'SUCCESS') {
					return $res->getResponse()->orders[0];
				}
			} else {
				throw new Exception('Invalid payment id');
			}
		} catch (Exception $e) {
			Log::error('PAYU_RETRIVE_ERR ' . $e->getMessage());
		}

		return 'ERROR';
	}

	function transaction(Order $order)
	{
		try {
			$p = Payment::where([
				'order_uid' => $order->uid
			])->first();

			if ($p) {
				$res = OpenPayU_Order::retrieveTransaction($p->id);

				return $res->getResponse()->transactions[0];
			} else {
				throw new Exception('Invalid payment id');
			}
		} catch (Exception $e) {
			Log::error('PAYU_TRANSACTION_ERR ' . $e->getMessage());
		}

		return 'ERROR';
	}

	function payments($lang = 'pl')
	{
		try {
			$res = OpenPayU_Retrieve::payMethods($lang);

			return $res->getResponse();
		} catch (Exception $e) {
			Log::error('PAYU_PAYMENTS_ERR ' . $e->getMessage());
		}

		return 'ERROR';
	}

	function notifyUrl(): string
	{
		// https://your.page/web/payment/notify/{gateway}
		return request()->getSchemeAndHttpHost() . '/web/payment/notify/payu';
	}

	function successUrl(Order $order): string
	{
		// https://your.page/web/payment/success/{order}
		return request()->getSchemeAndHttpHost() . '/web/payment/success/' . $order->id;
	}

	function ipAddress(): string
	{
		return request()->ip();
	}

	function lang(): string
	{
		return strtolower(app()->getLocale()) ?? 'pl';
	}

	function logo($square = false): string
	{
		if($square == true) {
			return 'vendor/pay/payu_square.png';
		}

		return 'vendor/pay/payu.png';
	}

	function toCents($decimal): string
	{
		return str_replace([',', ' ', '.'], '', trim((string) $decimal));
	}
}
