<?php
namespace App\Http\Controllers;
use App\Models\{AuditLog,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Hash};

class AuthController extends Controller {
    public function show(){ return view('auth.login'); }
    public function login(Request $r){
        $data=$r->validate(['username'=>'required|string','password'=>'required|string']);
        $username=mb_strtolower(trim($data['username']));
        $user=User::whereRaw('LOWER(username) = ?',[$username])->first();
        if($user?->locked_until?->isFuture()) return back()->withErrors(['username'=>'Acceso temporalmente bloqueado.'])->onlyInput('username');
        if(!$user || !Hash::check($data['password'],$user->password)){
            if($user){$attempts=$user->failed_login_attempts+1;$user->update(['failed_login_attempts'=>$attempts,'locked_until'=>$attempts>=5?now()->addMinutes(15):null]);}
            return back()->withErrors(['username'=>'Credenciales inválidas.'])->onlyInput('username');
        }
        Auth::login($user); $r->session()->regenerate(); $user->update(['failed_login_attempts'=>0,'locked_until'=>null,'last_login_at'=>now()]);
        AuditLog::create(['user_id'=>$user->id,'action'=>'admin.login','ip_address'=>$r->ip()]);
        return redirect()->intended('/admin');
    }
    public function logout(Request $r){ AuditLog::create(['user_id'=>Auth::id(),'action'=>'admin.logout','ip_address'=>$r->ip()]); Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/login'); }
}
