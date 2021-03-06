<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ProfileRequest;
use App\Model\Order\Invoice;
use App\Model\Order\Order;
use App\Model\Product\Product;
use App\Model\Product\Subscription;
use Bugsnag;
use DateTime;
use DateTimeZone;
use Exception;
use Hash;

class BaseClientController extends Controller
{
    /**
     * Get the version list popup for the Product.
     *
     * @param type $orders
     * @param type $productid
     *
     * @return type
     */
    public function getPopup($orders, $productid)
    {
        $listUrl = '';
        $productCheck = $orders->product()
        ->select('github_owner', 'github_repository', 'type')
        ->where('id', $orders->product)->first();
        if ($productCheck->type == 2) {
            if (!$productCheck->github_owner == '' && !$productCheck->github_repository == '') {
                $listUrl = $this->downloadGithubPopup($orders->client, $orders->invoice()->first()->id, $productid);
            } else {
                $listUrl = $this->downloadPopup($orders->client, $orders->invoice()->first()->number, $productid);
            }
        }

        return $listUrl;
    }

    /**
     * Get expiry Date for order.
     *
     * @param type $orders
     *
     * @return type
     */
    public function getExpiryDate($orders)
    {
        $end = '--';
        if ($orders->subscription()->first()) {
            if ($end != '0000-00-00 00:00:00' || $end != null) {
                $ends = new DateTime($orders->subscription()->first()->ends_at);
                $tz = \Auth::user()->timezone()->first()->name;
                $ends->setTimezone(new DateTimeZone($tz));
                $date = $ends->format('M j, Y, g:i a ');
                $end = $date;
                // dd($end);
            }
        }

        return $end;
    }

    public function downloadPopup($clientid, $invoiceid, $productid)
    {
        return view('themes.default1.front.clients.download-list',
            compact('clientid', 'invoiceid', 'productid'));
    }

    public function downloadGithubPopup($clientid, $invoiceid, $productid)
    {
        return view('themes.default1.front.clients.download-github-list',
            compact('clientid', 'invoiceid', 'productid'));
    }

    public function renewPopup($id, $productid)
    {
        return view('themes.default1.renew.popup', compact('id', 'productid'));
    }

    public function getActionButton($countExpiry, $countVersions, $link, $orderEndDate, $productid)
    {
        $getDownloadCondition = Product::where('id', $productid)->value('deny_after_subscription');
        if ($getDownloadCondition == 1) {
            if (strtotime($link['created_at']) < strtotime($orderEndDate->ends_at)) {
                $githubApi = new \App\Http\Controllers\Github\GithubApiController();

                $link = $githubApi->getCurl1($link['zipball_url']);

                return '<p><a href='.$link['header']['Location']." 
            class='btn btn-sm btn-primary'><i class='fa fa-download'>
            </i>&nbsp;&nbsp;Download</a>".'&nbsp;

      </p>';
            } else {
                return '<button class="btn btn-primary btn-sm disabled tooltip">
            Download <span class="tooltiptext">Please Renew!!</span></button>';
            }
        } elseif ($getDownloadCondition == 0) {
            if ($countExpiry == $countVersions) {
                $githubApi = new \App\Http\Controllers\Github\GithubApiController();
                $link = $githubApi->getCurl1($link['zipball_url']);

                return '<p><a href='.$link['header']['Location']." 
            class='btn btn-sm btn-primary'><i class='fa fa-download'>
            </i>&nbsp;&nbsp;Download</a>".'&nbsp;

      </p>';
            } else {
                return '<button class="btn btn-primary btn-sm disabled tooltip">
            Download <span class="tooltiptext">Please Renew!!</span></button>';
            }
        }
    }

    /**
     * Update Profile.
     */
    public function postProfile(ProfileRequest  $request)
    {
        try {
            $user = \Auth::user();
            if ($request->hasFile('profile_pic')) {
                $name = \Input::file('profile_pic')->getClientOriginalName();
                $destinationPath = 'dist/app/users';
                $fileName = rand(0000, 9999).'.'.$name;
                \Input::file('profile_pic')->move($destinationPath, $fileName);
                $user->profile_pic = $fileName;
            }
            $user->first_name = strip_tags($request->input('first_name'));
            $user->last_name = strip_tags($request->input('last_name'));
            $user->email = strip_tags($request->input('email'));
            $user->company = strip_tags($request->input('company'));
            $user->mobile_code = strip_tags($request->input('mobile_code'));
            $user->mobile = strip_tags($request->input('mobile'));
            $user->address = strip_tags($request->input('address'));
            $user->town = strip_tags($request->input('town'));
            $user->timezone_id = strip_tags($request->input('timezone_id'));
            $user->country = ($request->input('country'));
            $user->state = ($request->input('state'));
            $user->zip = strip_tags($request->input('zip'));
            $user->save();

            return redirect()->back()->with('success', \Lang::get('message.updated-successfully'));
        } catch (Exception $ex) {
            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    /**
     * Update Password.
     */
    public function postPassword(ProfileRequest $request)
    {
        try {
            $user = \Auth::user();
            $oldpassword = $request->input('old_password');
            $currentpassword = $user->getAuthPassword();
            $newpassword = $request->input('new_password');
            if (\Hash::check($oldpassword, $currentpassword)) {
                $user->password = Hash::make($newpassword);
                $user->save();
                $response = ['type'=>'success', 'message'=>'Password Updated Successfully'];

                return $response;
            } else {
                $response = ['type'=>'error', 'message'=>'Password Not Updated'];
            }
        } catch (\Exception $e) {
            $result = [$e->getMessage()];
            Bugsnag::notifyException($e);

            return response()->json(compact('result'), 500);
        }
    }

    public function getInvoicesByOrderId($orderid, $userid)
    {
        try {
            $order = Order::where('id', $orderid)->where('client', $userid)->first();

            $relation = $order->invoiceRelation()->pluck('invoice_id')->toArray();
            $invoice = new Invoice();
            $invoices = $invoice
                    ->select('number', 'created_at', 'grand_total', 'id', 'status')
                    ->whereIn('id', $relation);
            if ($invoices->get()->count() == 0) {
                $invoices = $order->invoice()
                        ->select('number', 'created_at', 'grand_total', 'id', 'status');
            }

            return \DataTables::of($invoices->get())
             ->addColumn('number', function ($model) {
                 return $model->number;
             })
            ->addColumn('products', function ($model) {
                $invoice = $this->invoice->find($model->id);
                $products = $invoice->invoiceItem()->pluck('product_name')->toArray();

                return ucfirst(implode(',', $products));
            })
            ->addColumn('date', function ($model) {
                $date = date_create($model->created_at);

                return date_format($date, 'M j, Y, g:i a');
            })
            ->addColumn('total', function ($model) {
                return $model->grand_total;
            })
            ->addColumn('status', function ($model) {
                return ucfirst($model->status);
            })
            ->addColumn('action', function ($model) {
                if (\Auth::user()->role == 'admin') {
                    $url = '/invoices/show?invoiceid='.$model->id;
                } else {
                    $url = 'my-invoice';
                }

                return '<a href='.url($url.'/'.$model->id)." 
                class='btn btn-sm btn-primary btn-xs'><i class='fa fa-eye' 
                style='color:white;'> </i>&nbsp;&nbsp;View</a>";
            })
                            ->rawColumns(['number', 'products', 'date', 'total', 'status', 'action'])
                            ->make(true);
        } catch (Exception $ex) {
            Bugsnag::notifyException($ex);

            return redirect()->back()->with('fails', $ex->getMessage());
        }
    }

    // public function getSubscriptions()
    // {
    //     try {
    //         $subscriptions = Subscription::where('user_id', \Auth::user()->id)->get();

    //         return \Datatable::collection($subscriptions)
    //                         ->addColumn('id', function ($model) {
    //                             return $model->id;
    //                         })
    //                         ->showColumns('created_at')
    //                         ->addColumn('ends_at', function ($model) {
    //                             return $model->subscription()->first()->ends_at;
    //                         })
    //                         ->searchColumns('id', 'created_at', 'ends_at')
    //                         ->orderColumns('created_at', 'ends_at')
    //                         ->make();
    //     } catch (Exception $ex) {
    //         Bugsnag::notifyException($ex);
    //         echo $ex->getMessage();
    //     }
    // }
}
