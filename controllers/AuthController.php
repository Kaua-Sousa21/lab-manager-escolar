<?php

declare(strict_types=1);

namespace Controllers;

use Models\AuditLog;
use Models\User;

class AuthController
{
    public static function login(string $email,string $password): array
    {
        $now=time();
        if(!empty($_SESSION['login_lock_until']) && $now < (int)$_SESSION['login_lock_until']){
            return ['success'=>false,'message'=>'Muitas tentativas de acesso. Aguarde um minuto e tente novamente.'];
        }
        $user=User::findByEmail($email);
        if(!$user || $user['status']!=='active' || !password_verify($password,$user['password'])){
            $_SESSION['login_attempts']=(int)($_SESSION['login_attempts']??0)+1;
            if($_SESSION['login_attempts']>=5){$_SESSION['login_lock_until']=$now+60;$_SESSION['login_attempts']=0;}
            return ['success'=>false,'message'=>'E-mail ou senha inválidos.'];
        }
        session_regenerate_id(true);
        $_SESSION['user_id']=(int)$user['id'];
        $_SESSION['user_name']=$user['name'];
        $_SESSION['user_role']=$user['role'];
        $_SESSION['last_activity']=$now;
        $_SESSION['last_regeneration']=$now;
        unset($_SESSION['login_attempts'],$_SESSION['login_lock_until']);
        AuditLog::record('login','auth',(int)$user['id'],'Acesso ao sistema');
        return ['success'=>true,'user'=>$user];
    }

    public static function logout(): void
    {
        if(isset($_SESSION['user_id'])) AuditLog::record('logout','auth',(int)$_SESSION['user_id'],'Saída do sistema');
        $_SESSION=[];
        if(ini_get('session.use_cookies')){
            $params=session_get_cookie_params();
            setcookie(session_name(),'',time()-42000,$params['path'],$params['domain'],$params['secure'],$params['httponly']);
        }
        session_destroy();
    }

    public static function isAuthenticated(): bool { return isset($_SESSION['user_id']); }

    public static function requireAuth(): void
    {
        if(!self::isAuthenticated()){ flash('warning','Entre para acessar o sistema.'); redirect('/views/auth/login.php'); }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        if(!in_array($_SESSION['user_role']??'', $roles,true)){ flash('danger','Você não possui permissão para acessar esta área.'); redirect('/dashboard/index.php'); }
    }

    public static function getUser(): ?array { return self::isAuthenticated()?User::find((int)$_SESSION['user_id']):null; }
}
