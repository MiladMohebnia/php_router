<?php

use miladm\DataObject;
use miladm\Route;
use miladm\oldRouter\router\RequestDataObject;

class UserDO extends DataObject
{
    public $name;
    public $role;

    public function isAdmin()
    {
        return $this->role == 'admin';
    }
}

class IndexGetDO extends DataObject
{
    public $user;
    public $token;

    public function init()
    {
        $this->user = UserDO::class;
    }

    public function validate()
    {
        return $this->token == '123';
    }
}

class IndexRDO extends RequestDataObject
{
    public function init()
    {
        $this->get = IndexGetDO::class;
    }
}

Route::expect(IndexRDO::class);

Route::get('', function ($request) {
    die(var_dump(
        $request->validate(),
        $request->get,
        $request->get->user->isAdmin()
    ));
});



// Route::get('', function ($request) {
//     die(var_dump(
//         $request->get
//     ));
// })->expect(IndexRDO::class);
