<?php

namespace App\Http\Controllers\Order;

use App\Model\Order\Invoice;
use App\Model\Order\InvoiceItem;
use App\Model\Order\Order;
use App\Model\Payment\Plan;
use App\Model\Product\Product;
use App\Model\Product\Subscription;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Session;

class RenewController extends BaseRenewController
{
    protected $sub;
    protected $plan;
    protected $order;
    protected $invoice;
    protected $item;
    protected $product;
    protected $user;

    public function __construct()
    {
        $sub = new Subscription();
        $this->sub = $sub;

        $plan = new Plan();
        $this->plan = $plan;

        $order = new Order();
        $this->order = $order;

        $invoice = new Invoice();
        $this->invoice = $invoice;

        $item = new InvoiceItem();
        $this->item = $item;

        $product = new Product();
        $this->product = $product;

        $user = new User();
        $this->user = $user;
    }

    public function renewBySubId($id, $planid, $payment_method, $cost, $code)
    {
        try {
            $plan = $this->plan->find($planid);
            $days = $plan->days;
            $sub = $this->sub->find($id);
            $current = $sub->ends_at;
            $ends = $this->getExpiryDate($current, $days);
            $sub->ends_at = $ends;
            $sub->save();
            $this->invoiceBySubscriptionId($id, $planid, $cost);

            return $sub;
        } catch (Exception $ex) {
            dd($ex);

            throw new Exception($ex->getMessage());
        }
    }

    public function successRenew($invoice)
    {
        try {
            $invoice->status = 'success';
            $invoice->save();

            $id = Session::get('subscription_id');

            $planid = Session::get('plan_id');
            $plan = $this->plan->find($planid);
            $days = $plan->days;
            $sub = $this->sub->find($id);
            $current = $sub->ends_at;
            $ends = $this->getExpiryDate($current, $days);
            $sub->ends_at = $ends;
            $sub->save();
            $this->removeSession();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    //Tuesday, June 13, 2017 08:06 AM

    public function getProductById($id)
    {
        try {
            $product = $this->product->where('id', $id)->first();
            if ($product) {
                return $product;
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function getUserById($id)
    {
        try {
            $user = $this->user->where('id', $id)->first();
            if ($user) {
                return $user;
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function createOrderInvoiceRelation($orderid, $invoiceid)
    {
        try {
            $relation = new \App\Model\Order\OrderInvoiceRelation();
            $relation->create([
                'order_id'   => $orderid,
                'invoice_id' => $invoiceid,
            ]);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function getPriceByProductId($productid, $userid)
    {
        try {
            $product = $this->getProductById($productid);
            if (!$product) {
                throw new Exception('Product has removed from database');
            }
            $currency = $this->getUserCurrencyById($userid);
            $price = $product->price()->where('currency', $currency)->first();
            if (!$price) {
                throw new Exception('Price has removed from database');
            }
            $cost = $price->sales_price;
            if (!$cost) {
                $cost = $price->regular_price;
            }

            return $cost;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function getUserCurrencyById($userid)
    {
        try {
            $user = $this->user->find($userid);
            if (!$user) {
                throw new Exception('User has removed from database');
            }

            return $user->currency;
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    public function tax($product, $cost, $userid)
    {
        try {
            $controller = new InvoiceController();
            $tax = $controller->checkTax($product->id, $userid);
            $tax_name = '';
            $tax_rate = '';
            if (!empty($tax)) {

                    //dd($value);
                $tax_name .= $tax[0].',';
                $tax_rate .= $tax[1].',';
            }
            $grand_total = $controller->calculateTotal($tax_rate, $cost);

            return \App\Http\Controllers\Front\CartController::rounding($grand_total);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    public function renew($id, Request $request)
    {
        $this->validate($request, [
            'plan'           => 'required',
            'payment_method' => 'required',
            'cost'           => 'required',
            'code'           => 'exists:promotions,code',
        ]);

        try {
            $planid = $request->input('plan');
            $payment_method = $request->input('payment_method');
            $code = $request->input('code');
            $cost = $request->input('cost');
            $renew = $this->renewBySubId($id, $planid, $payment_method, $cost, $code = '');

            if ($renew) {
                return redirect()->back()->with('success', 'Renewed Successfully');
            }

            return redirect()->back()->with('fails', 'Can not Process');
        } catch (Exception $ex) {
            return redirect()->back()->back()->with('fails', $ex->getMessage());
        }
    }

    public function renewForm($id)
    {
        try {
            $sub = $this->sub->find($id);
            $userid = $sub->user_id;
            $plans = $this->plan->pluck('name', 'id')->toArray();

            return view('themes.default1.renew.renew', compact('id', 'plans', 'userid'));
        } catch (Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    public function renewByClient($id, Request $request)
    {
        $this->validate($request, [
            'plan'           => 'required',
            'payment_method' => 'required',
            'cost'           => 'required',
            'code'           => 'exists:promotions,code',
        ]);

        try {
            $planid = $request->input('plan');
            $payment_method = $request->input('payment_method');
            $code = $request->input('code');
            $cost = $request->input('cost');
            $items = $this->invoiceBySubscriptionId($id, $planid, $cost);
            $invoiceid = $items->invoice_id;
            $this->setSession($id, $planid);

            return redirect('paynow/'.$invoiceid);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
    }

    public function setSession($sub_id, $planid)
    {
        Session::put('subscription_id', $sub_id);
        Session::put('plan_id', $planid);
    }

    public function removeSession()
    {
        Session::forget('subscription_id');
        Session::forget('plan_id');
        Session::forget('invoiceid');
    }

    public function checkRenew()
    {
        $res = false;
        if (Session::has('subscription_id') && Session::has('plan_id')) {
            $res = true;
        }

        return $res;
    }

    public function getExpiryDate($end, $days)
    {
        $date = Carbon::parse($end);
        $expiry_date = $date->addDay($days);

        return $expiry_date;
    }
}
