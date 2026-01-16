<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\AdWallet;
use App\Models\User;

class AdWalletRepository extends Repository
{
    public static function model()
    {
        return AdWallet::class;
    }

    /**
     * wallet store by request
     */
    public static function storeByRequest(User $user): AdWallet
    {
        return self::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);
    }

    /**
     * wallet update by request
     *
     * @param  float  $balance
     * @param  string  $type  (credit or debit)
     */
    public static function updateByRequest(AdWallet $wallet, $balance, $type): AdWallet
    {
        // ballance increase or decrease
        $ballance = $type == 'credit' ? $wallet->balance + $balance : $wallet->balance - $balance;

        $wallet->update([
            'balance' => $ballance,
        ]);

        return $wallet;
    }

    public static function getAdminAdWallet(): AdWallet
    {
        $role = 'root';

        $user = User::whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->first();

        return $user->wallet;
    }
}