<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public function __construct()
    {
        // auth znaci da moras biti ulogovan da mi prisao akciji
        $this->middleware('auth')->except(['login']);

        // samo admin ili moderator ako menja sebe, tj. svoje podatke
        $this->middleware('onlyadmin')->except(['welcome', 'edit', 'update', 'logout', 'changepassword', 'changepasswordpost', 'login']);

        // vrsi redirekciju ako si vec ulogovan
        // samo za "gost" user-e
        $this->middleware('guest')->only(['login']);
    }

    public function index(){
        // $users = User::where('deleted', 0)->get();
        $users = User::notdeleted()->get();
        return view('admin.users.index', compact('users'));
    }

    public function login(){

        // ako je post probaj login
        if(request()->isMethod('post')){
            // prvo ide validacija
            request()->validate([
                'email' => "required|string|email",
                'password' => "required"
            ]);

            // onda ide proba logina
            if(auth()->attempt([
                'email' => request()->email,
                'password' => request()->password,
                'active' => 1
            ])){
                // ako je uspesan login 
                // redirektuj gde kaze vlasnik portala
                return redirect()->intended(route('users.welcome'))
                        ->with(['message' => [
                            'type' => 'text-success',
                            'text' => 'Welcome again!!!'
                        ]]);
            } else {
                // ako nije uspesan login
                // redirektuj nazad na login formu 
                // sa greskom da je nesto lose
                return redirect()->route('users.login')
                                ->withErrors(['email' => trans('auth.failed')])
                                ->withInput(['email' => request()->email]);
            }
        } 
        
        // ovo se desava ako je get
        return view('admin.users.login');
    }

    public function welcome(){
        
        return view('admin.users.welcome');
    }

    public function create(){
        
        return view('admin.users.create');
    }

    public function store(){
        // validacija
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string|in:administrator,moderator',
            'password' => 'required|string|min:5|confirmed',
            // 'password-confirmed' => "same:password",
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        $data['active'] = 1;
        $data['password'] = Hash::make($data['password']);

        // snimaje u bazu
        User::create($data);

        // redirekcija

        return redirect()
                ->route('users.index')
                ->with('message', [
                    'type' => 'text-success',
                    'text' => trans('users.success-created')
                ]);
    }

    public function edit(User $user){
        if(auth()->user()->role != "administrator" && auth()->id() != $user->id){
            // pokusao si da menjas kao moderator nekog ciji id nije tvoj
            abort(401, "No privilegies");
        }
        
        return view('admin.users.edit', compact('user'));
    }

    public function update(User $user){
        if(auth()->user()->role != "administrator" && auth()->id() != $user->id){
            // pokusao si da menjas kao moderator nekog ciji id nije tvoj
            abort(401, "No privilegies");
        }

        // validacija
        $data = request()->validate([
            'name' => 'required|string|max:255',
            // 'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string|in:administrator,moderator',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        // snimanje
        $user->name = $data['name'];
        $user->address = $data['address'];
        $user->phone = $data['phone'];
        if(auth()->user()->role == "administrator"){
            $user->role = $data['role'];
        }

        $user->save();

        // redirekcija
        return redirect()
                ->route('users.index')
                ->with('message', [
                    'type' => 'text-success',
                    'text' => trans('users.success-updated')
                ]);
    }

    public function changepassword(User $user){
        if(auth()->user()->role != "administrator" && auth()->id() != $user->id){
            // pokusao si da menjas kao moderator nekog ciji id nije tvoj
            abort(401, "No privilegies");
        }
        
        return view('admin.users.changepassword', compact('user'));
    }

    public function changepasswordpost(User $user){
        if(auth()->user()->role != "administrator" && auth()->id() != $user->id){
            // pokusao si da menjas kao moderator nekog ciji id nije tvoj
            abort(401, "No privilegies");
        }
        
        // validacija
        $data = request()->validate([
            'password' => 'required|string|min:5|confirmed',
        ]);

        // snimanje
        $user->password = Hash::make($data['password']);
        $user->save();

        // redirekcija
        if(auth()->user()->role == 'administrator'){
            // admin ide na listu svih korisnika
            return redirect()
                    ->route('users.index')
                    ->with('message', [
                        'type' => 'text-success',
                        'text' => trans('users.success-updated')
                    ]);
        } else {
            // svi ostali idu na dashboard sa porukom nekom da je uspesno
            return redirect()
                ->route('users.welcome')
                ->with('message', [
                    'type' => 'text-success',
                    'text' => trans('users.success-updated')
                ]);
        }
    }

    public function status(User $user){

        if($user->active == 1){
            $user->active = 0;
        } else {
            $user->active = 1;
        }
        $user->save();

        // redirekcija
        return redirect()
                ->route('users.index')
                ->with('message', [
                    'type' => 'text-success',
                    'text' => trans('users.changed-status')
                ]);
    }

    public function delete(User $user){
        // $user->delete();
        if(auth()->user()->role == "administrator"){
            $user->deleted = 1;
            $user->deleted_by = auth()->user()->id;
            $user->save();
        } else {
            Log::info("User " . auth()->user()->name . ' trid to delete user ' . $user->name);
            Auth::logout();
            // redirekcija
            return redirect()
            ->route('users.login')
            ->with('message', [
                'type' => 'text-success',
                'text' => trans('users.no-auth')
            ]);
        }

        // redirekcija
        return redirect()
                ->route('users.index')
                ->with('message', [
                    'type' => 'text-success',
                    'text' => trans('users.success-deleted')
                ]);
    }

    public function logout(){
        Auth::logout();

        request()->session()->invalidate();
 
        request()->session()->regenerateToken();

        return redirect(route('users.login'));

    }
}
