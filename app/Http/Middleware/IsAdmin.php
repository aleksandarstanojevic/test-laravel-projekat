<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        // samo ulogovani korisnik, ali koji je administrator
        // moze da pridje ovim rutama
        if(auth()->user()->role !== 'administrator'){
            // nemas privilegije da ides dalje
            // uradi nesto od ovog dole
            // 1. izloguj i redirektuj na login
            // auth()->logout();
            // return redirect('/users/login);

            // 2. redirektuj ga na njegov home page, sta god da je to
            // ako si moderator homepage /users/welcome
            // ali ispisi poruku da se desilo to sto se desilo
            // return redirect('/users/welcome)->with(''); // podesis flash poruku da je pokusao da pridje
            // resursu za koji nema privilegije

            // 3. abort sa 403 ili 401 statusom
            abort(401, "Neka poruka");
        }

        return $next($request);
    }
}
