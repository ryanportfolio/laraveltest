<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return view('products');
    }

    public function list()
    {
        return response()->json($this->readProducts());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        $products = $this->readProducts();

        $ids = array_column($products, 'id');

        $products[] = [
            'id' => $ids ? max($ids) + 1 : 1,
            'name' => $data['name'],
            'quantity' => (int) $data['quantity'],
            'price' => (float) $data['price'],
            'submitted_at' => date('Y-m-d H:i:s'),
        ];

        $this->writeProducts($products);

        return response()->json($products);
    }

    private function readProducts()
    {
        if (!Storage::exists('products.json')) {
            return [];
        }

        $products = json_decode(Storage::get('products.json'), true);

        return is_array($products) ? $products : [];
    }

    private function writeProducts(array $products)
    {
        Storage::put('products.json', json_encode($products, JSON_PRETTY_PRINT));
    }
}
