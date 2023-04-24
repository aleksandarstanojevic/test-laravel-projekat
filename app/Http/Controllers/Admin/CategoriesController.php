<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;

class CategoriesController extends Controller
{

    public function index(){
        // $users = User::where('deleted', 0)->get();
        $categories = Category::orderBy('priority', 'ASC')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create(){
        return view('admin.categories.create');
    }

    public function store(){
        // validacija
        $data = request()->validate([
            'name' => 'required|string|min:3|max:20',
            'description' => 'required|string|min:10|max:255',
            'text' => 'nullable|string|max:65000',
            'active' => 'between:0,1'
        ]);

        $dataPriority = Category::orderBy('priority', 'DESC')->first();
        $data['priority'] = $dataPriority['priority'] + 1;

        // snimaje u bazu
        Category::create($data);

        // redirekcija

        return redirect()
                ->route('categories.index')
                ->with('message', [
                    'type' => 'text-success',
                    'text' => trans('category.success-created')
                ]);
    }

    public function show($id){
        //
    }

    public function edit(Category $category){
        
        return view('admin.categories.edit', compact('category'));
    }

    public function updateOrder(Request $request){
        if($request->has('ids')){
            $arr = explode(',',$request->input('ids'));
            
            foreach($arr as $sortOrder => $id){
                $menu = Category::find($id);
                $menu->sort_id = $sortOrder;
                $menu->save();
            }
            return ['success'=>true,'message'=>'Updated'];
        }
    }

    public function update(Category $category){

        // validacija
        $data = request()->validate([
            'name' => 'required|string|min:3|max:20',
            'description' => 'required|string|min:10|max:255',
            'text' => 'nullable|string|max:65000',
        ]);

        // snimanje
        $category->name = $data['name'];
        $category->description = $data['description'];
        $category->text = $data['text'];


        $category->save();

        // redirekcija
        return redirect()
                ->route('categories.index')
                ->with('message', [
                    'type' => 'text-success',
                    'text' => trans('categories.success-updated')
                ]);
    }

    public function status(Category $category){

        if($category->active == 1){
            $category->active = 0;
        } else {
            $category->active = 1;
        }
        $category->save();

        // redirekcija
        return redirect()
                ->route('categories.index')
                ->with('message', [
                    'type' => 'text-success',
                    'text' => trans('categories.changed-status')
                ]);
    }

    public function delete(Category $category){
        $categories = Category::whereNull('deleted_at')->where('priority', '>', $category->priority)->get();

        $category->delete();

        foreach($categories as $value){
            $value->priority --;
            $value->save();
        }
        
        // redirekcija
        return redirect()
                ->route('categories.index')
                ->with('message', [
                    'type' => 'text-success',
                    'text' => trans('categories.success-deleted')
                ]);
    }
}
