<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\Cart;
use Illuminate\Support\Str;
use Helper;

class CartController extends Controller
{
    private $errorMsgInvalidProduct = 'Invalid product';
    private $errorMsgStockNotSufficient = 'Stock not sufficient';

    protected $product=null;
    public function __construct(Product $product){
        $this->product=$product;
    }

    public function addToCart(Request $request){

        $product = Product::where('slug', $request->slug)->first();

        if (empty($request->slug) || empty($product)) {
            request()->session()->flash('error',$this->errorMsgInvalidProduct);
            return back();
        }

        return $this->processProductToCart($product);
    }

    private function processProductToCart($product) {
        $already_cart = Cart::where('user_id', auth()->user()->id)
            ->where('order_id',null)
            ->where('product_id', $product->id)
            ->first();

        if($already_cart) {
            $already_cart->quantity = $already_cart->quantity + 1;
            $already_cart->amount = $product->price + $already_cart->amount;
            if ($already_cart->product->stock < $already_cart->quantity || $already_cart->product->stock <= 0) {
                return back()->with('error', $this->errorMsgStockNotSufficient);
            }
            $already_cart->save();

        }else{

            $cart = new Cart;
            $cart->user_id = auth()->user()->id;
            $cart->product_id = $product->id;
            $cart->price = ($product->price-($product->price*$product->discount)/100);
            $cart->quantity = 1;
            $cart->amount=$cart->price*$cart->quantity;
            if ($cart->product->stock < $cart->quantity || $cart->product->stock <= 0) {
                return back()->with('error', $this->errorMsgStockNotSufficient);
            }
            $cart->save();
            $wishlist=Wishlist::where('user_id',auth()->user()->id)
                ->where('cart_id',null)
                ->update(['cart_id'=>$cart->id]);
        }
        request()->session()->flash('success','Product successfully added to cart');
        return back();
    }

    public function singleAddToCart(Request $request){
        $request->validate([
            'slug'      =>  'required',
            'quant'      =>  'required',
        ]);

        $product = Product::where('slug', $request->slug)->first();

        if($product->stock <$request->quant[1]){
            return back()->with('error','Out of stock, You can add other products.');
        }

        if ( ($request->quant[1] < 1) || empty($product) ) {
            request()->session()->flash('error',$this->errorMsgInvalidProduct);
            return back();
        }

        return $this->processProductToCartSingle($product, $request->quant[1]);
    }

    private function processProductToCartSingle($product, $reqQuantity) {
        $already_cart = Cart::where('user_id', auth()->user()->id)
            ->where('order_id',null)
            ->where('product_id', $product->id)->first();

        if($already_cart) {
            $already_cart->quantity = $already_cart->quantity + $reqQuantity;
            $already_cart->amount = ($product->price * $reqQuantity)+ $already_cart->amount;

            if ($already_cart->product->stock < $already_cart->quantity || $already_cart->product->stock <= 0) {
                return back()->with('error', $this->errorMsgStockNotSufficient);
            }

            $already_cart->save();

        }else{

            $cart = new Cart;
            $cart->user_id = auth()->user()->id;
            $cart->product_id = $product->id;
            $cart->price = ($product->price-($product->price*$product->discount)/100);
            $cart->quantity = $reqQuantity;
            $cart->amount=($product->price * $reqQuantity);
            if ($cart->product->stock < $cart->quantity || $cart->product->stock <= 0) {
                return back()->with('error', $this->errorMsgStockNotSufficient);
            }
            $cart->save();
        }
        request()->session()->flash('success','Product successfully added to cart.');
        return back();
    }

    public function cartDelete(Request $request){
        $cart = Cart::find($request->id);
        if ($cart) {
            $cart->delete();
            request()->session()->flash('success','Cart successfully removed');
            return back();
        }
        request()->session()->flash('error','Error please try again');
        return back();
    }

    public function cartUpdate(Request $request){

        if (!$request->quant) {
            return back()->with('Cart Invalid!');
        }

        $error = array();
        $success = '';

        foreach ($request->quant as $k=>$quant) {
            $id = $request->qty_id[$k];
            $cart = Cart::find($id);

            $isAnyItemInCart = $quant > 0 && $cart;
            $isProductStockAvailable = $cart->product->stock < $quant;
            $isProductStockEmpty = $cart->product->stock <=0;

            if ($isAnyItemInCart && $isProductStockAvailable) {
                request()->session()->flash('error','Out of stock');
                return back();
            }

            if ($isAnyItemInCart && $isProductStockEmpty) {
                continue;
            }

            if ($isAnyItemInCart) {
                $cart->quantity = ($cart->product->stock > $quant) ? $quant  : $cart->product->stock;
                $after_price = ($cart->product->price-($cart->product->price*$cart->product->discount)/100);
                $cart->amount = $after_price * $quant;
                $cart->save();
                $success = 'Cart successfully updated!';
            } else {
                $error[] = 'Cart Invalid!';
            }
        }

        return back()->with($error)->with('success', $success);
    }

    public function checkout(Request $request){
        return view('frontend.pages.checkout');
    }
}
