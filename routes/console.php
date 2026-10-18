<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
Artisan::command('app:create-admin', function () {
 $name=$this->ask('Administrator display name'); $email=$this->ask('Administrator email'); $password=$this->secret('Password (12+ characters)');
 $v=Validator::make(compact('name','email','password'),['name'=>'required|string|max:120','email'=>'required|email|unique:users,email','password'=>'required|string|min:12']);
 if($v->fails()){foreach($v->errors()->all() as $error)$this->error($error);return 1;}
 $u=User::create(['name'=>$name,'email'=>$email,'password'=>Hash::make($password),'is_admin'=>true]);
 $u->forceFill(['is_active'=>true,'email_verified_at'=>now()])->save();
 $this->info('Administrator created. No default credentials are shipped.');
})->purpose('Create administrator interactively');
