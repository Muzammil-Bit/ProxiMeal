<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\FaqDataTable;
use App\DataTables\TransactionsDataTable;
use App\DataTables\WalletDataTable;
use App\Models\User;
use App\Transaction;

class WalletController extends Controller
{
    /**
     * Display a listing of the Faq.
     *
     * @param WalletDataTable $walletDataTable
     * @return Response
     */
    public function index(WalletDataTable $walletDataTable)
    {
        return $walletDataTable->render('wallets.index');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        return view('wallets.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|starts_with:minus,plus',
            'value' => 'required|integer|min:1',
        ]);
        $value = $request->value;
        $user_id = $request->user_id;

        if ($request->type == "minus") {
            if ($value > Transaction::where('user_id', $user_id)->sum('value')) {
                return back()->with('flash', flash("User Does not have engough credit."));
            }
            $value = -$value;
        }

        $tranasction = new Transaction();
        $tranasction->user_id = $user_id;
        $tranasction->value = $value;
        $tranasction->description = $request->description;


        if ($tranasction->save()) {
            return back()->with('flash', flash("Transaction Addedd Successfully"));
        }

        throw new \Exception();
    }



    public function userTransactions($id)
    {
        $transactions = Transaction::where('user_id', $id)->get();
        return view('wallets.transactions-show')->with([
            'transactions' => $transactions,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
