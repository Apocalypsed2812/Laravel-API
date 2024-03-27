<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Account;
use Illuminate\Http\Request;

class AdminController extends ApiController
{
    public function getAllProduct(){
        try{
            $products = Product::All();
            if(!$products){
                return $this->sendResponse([], false, 'Product Not Found', 400);
            }
            return $this->sendResponse($products->toArray());
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function getProductById(Request $request){
        try{
            $id = $request->input('id');
            $product = Product::find($id);

            if(!$product){
                return $this->sendResponse([], false, 'Product Not Found', 400);
            }
            return $this->sendResponse($product->toArray());
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function addProduct(Request $request){
        try{
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
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function deleteProduct(Request $request){
        try{
            $id = $request->input('id');

            if(!$id){
                return $this->sendResponse([], false, 'Id Is Required', 400);
            }

            $product = Product::find($id);
            if($product){
                $product->delete();
                return $this->sendResponse([], true, 'Delete Product Successfully', 200);
            }
            return $this->sendResponse([], false, 'Product Not Found', 400);
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function updateProduct(Request $request){
        try{
            $request->validate([
                'name' => 'required|string',
                'price' => 'required|integer|min:0',
                'description' => 'required|string',
                'quantity' => 'required|integer|min:0',
            ], [
                'name.required' => 'Please enter product name.',
                'price.required' => 'Please enter product price.',
                'description.required' => 'Please enter product description',
                'quantity.reuqired' => 'Please enter product quantity',
            ]);

            $id = $request->input('id');

            if(!$id){
                return $this->sendResponse([], false, 'Id Is Required', 400);
            }

            $name = $request->name;
            $price = $request->price;
            $description = $request->description;
            $quantity = $request->quantity;

            $product = Product::find($id);

            if($product){
                $product->update([
                    "name" => $name,
                    "price" => $price,
                    "description" => $description,
                    "quantity" => $quantity,
                ]);
                return $this->sendResponse([], true, 'Update Product Successfully', 200);
            }

            return $this->sendResponse([], false, 'Product Not Found', 400);
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function getAllUser(){
        try{
            $users = Account::All();

            if(!$users){
                return $this->sendResponse([], false, 'User Not Found', 400);
            }

            return $this->sendResponse($users->toArray());
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }

    public function getUserById(Request $request){
        try{
            $id = $request->input('id');
            $user = Account::find($id);

            if(!$user){
                return $this->sendResponse([], false, 'User Not Found', 400);
            }
            return $this->sendResponse($user->toArray());
        }
        catch(\Exception $e){
            return $this->sendResponse([], false, $e->getMessage(), 400);
        }
    }
}
