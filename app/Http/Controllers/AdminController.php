<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends ApiController
{
    public function getAllProduct(){
        $products = Product::All();
        return $this->sendResponse($products->toArray());
    }

    public function getProductById(Request $request){
        $id = $request->input('id');
        $product = Product::find($id);

        if(!$product){
            return $this->sendResponse([], false, 'Product Not Found', 400);
        }
        return $this->sendResponse($product->toArray());
    }

    public function addProduct(Request $request){
        $data = $request->validate([
            'name' => 'required|string',
            'price' => 'required|integer|min:0',
            'description' => 'required|string',
            'quantity' => 'required|integer|min:0',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'name.required' => 'Please enter product name.',
            'price.required' => 'Please enter product price.',
            'description.required' => 'Please enter product description',
            'quantity.reuqired' => 'Please enter product quantity',
        ]);

        $product = new Product([
            'name' => $data['name'],
            'price' => $data['price'],
            'description' => $data['description'],
            'quantity' => $data['quantity'],
        ]);

        if($request->hasFile('image')){
            $imagePath = $request->file('image')->store('image', 'public');
            $data['image'] = $imagePath;
        }

        $product = new Product($data);
        $product->save();

        return $this->sendResponse([], true, 'Add Product Successfully', 200);
    }

    public function deleteProduct(Request $request){
        $id = $request->input('id-delete');

        $product = Product::find($id);
        if($product){
            $product->delete();
        }
        return $this->sendResponse([], true, 'Delete Product Successfully', 200);
    }

    public function updateProduct(Request $request){
        $id = $request->input('id-edit');
        $name = $request->input('name-edit');
        $price = $request->input('price-edit');
        $description = $request->input('description-edit');
        $quantity = $request->input('quantity-edit');

        $product = Product::find($id);

        if($product){
            $product->update([
                "name" => $name,
                "price" => $price,
                "description" => $description,
                "quantity" => $quantity,
            ]);
        }

        return $this->sendResponse([], true, 'Update Product Successfully', 200);
    }

    public function getAllUser(){
        $users = User::All();
        return $this->sendResponse($users->toArray());
    }

    public function getUserById(Request $request){
        $id = $request->input('id');
        $user = User::find($id);

        if(!$user){
            return $this->sendResponse([], false, 'User Not Found', 400);
        }
        return $this->sendResponse($user->toArray());
    }
}
