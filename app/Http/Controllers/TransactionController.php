<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // dd('masuk index');
        $transactions = Transaction::latest();
        return view('pages.transaction.index', [
            'transactions' => $transactions->paginate(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Query items
        $items = Item::all();

        // query all cart items
        $savedCart = collect($request->session()->get('cart', []));
        $carts = Item::query()->whereIn('id', $savedCart->pluck('item_id'))->get();

        $carts->each(function ($cart) use ($savedCart) {
            // dd($savedCart->firstWhere('item_id', $cart->id));
            // $cart->{'qty'} = $savedCart->firstWhere('item_id', $cart->id)['qty'];
            $cart->{'qty'} = $savedCart->firstWhere('item_id', $cart->id)->qty;
        });

        // dd($carts);

        // return view
        // $value =  0;
        // $value = 'im string';
        return view('pages.transaction.create', [
            'items' => $items,
            'carts' => $carts
        ]);
    }

    /**
     * \ a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd('masuk sotrew');
        DB::beginTransaction();
        try {
            // get items in cart
            $savedCart = collect($request->session()->get('cart'));

            // create new Transaction instance
            $transaction = $request->user()->transactions()->create([
                'payment_method' => '',
                'total' => $savedCart->sum(fn($c) => $c->qty * $c->price)
            ]);

            // save item in cart to transaction
            $savedCart->each(function ($cart) use ($transaction) {
                $item = Item::findOrFail($cart->item_id);
                $transaction->items()->attach($item, [
                    'qty' => $cart->qty,
                    'price' => $item->price,
                ]);
            });

            // clear cart
            $request->session()->forget('cart');
            // $request->session()->flush();

            // commit save to database
            DB::commit();

            return redirect()->route('transaction.index')->with('success', 'Berhasil membuat transaksi');
        } catch (Exception $e) {
            DB::rollBack(); // rollback if something wrong happened
            // dd($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi Kesalahan');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }

    public function addCart(Item $item, Request $request)
    {
        //get exist cart
        $cart = collect($request->session()->get('cart'));

        if ($cart->firstWhere('item_id', $item->id) == null) {
            $cart->push((object)[
                'item_id' => $item->id,
                'qty' => 1,
                'price' => $item->price,
            ]);
        } else { // otherwise add qty on existing cart
            $cart->firstWhere('item_id', $item->id)->qty += 1   ;
            $cart->firstWhere('item_id', $item->id)->price = $item->price * $cart->firstWhere('item_id', $item->id)->qty;
            // $cartItem = $cart->firstWhere('item_id', $item->id);
            // $cartItem['qty']++;
            // $cartItem['price'] = $item->price * $cartItem['qty'];

            // $cart = $cart->map(function ($entry) use ($item) {
            //     if ($entry['item_id'] == $item->id) {
            //         $entry['qty']++;
            //         $entry['price'] = $item->price * $entry['qty'];
            //     }
            //     return $entry;
            // });
        }

        $request->session()->put('cart', $cart);
        $request->session()->save();

        return redirect()->back()->with('success', 'Berhasil menambahkan barang ke keranjang');
    }

    public function reduceCart(Item $item, Request $request)
    {
        // get existing cart
        $cart = collect($request->session()->get('cart'));

        // check if selected item is on the cart
        $selectedCart = $cart->firstOrFail(fn($c) => $c->item_id == $item->id);

        $selectedCart->qty -= 1;
        $selectedCart->price = $item->price * $selectedCart->qty;

        // save cart, if qty is less than 0, delete the instance
        $request->session()->put('cart', $cart->filter(fn($c) => $c->qty > 0));
        $request->session()->save();

        // redirect back
        return redirect()->back()->with('success', 'Berhasil menambahkan barang ke keranjang');
    }
}
