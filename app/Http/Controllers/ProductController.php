<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $products = new Product();

        $products = $products->orderBy('created_at', 'desc');

        $products = $products->paginate();


        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        //
        $validated = $request->validated();

        $product = Product::create($validated);

        return ProductResource::make($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
        return ProductResource::make($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        //
        // return $request->file('addImages')->getClientOriginalName();
        $validated = $request->validated();

        // return $request->file('photos')->getClientOriginalName();

        // // if ($request->hasFile('photos')) {
        //     foreach ($request->photos as $file)
        //         return $file;
        // // }

        //return 'hi';

        $product->update($validated);



        return ProductResource::make($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
        $product->delete();

        return ProductResource::make($product);
    }
    public static function uploadFileToS3(Request $request)
    {
        $destination_path = 'products_uploads';
        $product_id = $request->get('product_id', null);

        if (!isset($product_id)) {
            return response()->json(['message' => 'ProductApiController.uploadFileToS3 method called without product_id.'], 400);
        }
        // get hold of the uploaded file.
        $files = $request->allFiles();
        if (empty($files)) {
            return response()->json(['message' => 'ProductApiController.uploadFileToS3 method called without any files.'], 400);
        }
        $product = Product::find($product_id);
        if (!isset($product)) {
            return response()->json(['message' => 'Unable to resolve Product while invoking uploadFileToS3. Id given=' . $product_id], 400);
        }

        /**
         * @var integer $idx
         * @var UploadedFile $file
         */
        $return = [];

        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $idx => $file) {
            $destination_path = 'document_uploads';
            $filename = md5($file->getClientOriginalName() . time()) . '.' . $file->getClientOriginalExtension();
            // to store on s3
            $r = Storage::disk('s3')->putFileAs($destination_path, new \Illuminate\Http\File($file->path()), $filename);
            $newPath = trim($destination_path . '/' . $filename, '/');
            //Todo for saving this in the database we need to create document table.
            // $document = new Document();
            // $document->file_name = $file->getClientOriginalName();
            // $document->path = $newPath;
            // $document->parent_id = $product->id;
            // $document->report_type = null;
            // $document->entity_name = get_class($product);
            // $document->caption =  "Product";
            // $document->save();

            $return[] = [
                'file_name' => $file->getClientOriginalName(),
                'saved_path' => $newPath
            ];
        }
        return response()->json($return, 200);
    }
}
