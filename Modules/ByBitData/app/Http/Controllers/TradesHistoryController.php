<?php

namespace Modules\ByBitData\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ByBitData\Services\SymbolServices;
use Modules\ByBitData\Services\TradesHistoryService;

class TradesHistoryController extends Controller
{
    public function __construct(
        protected TradesHistoryService $tradesHistoryService,
        protected SymbolServices $symbolServices
    ){}

    public function index()
    {
        $symbols =$this->symbolServices->get();

        $filter = [
            'symbol' => request('filter.symbol'),
            'tf' => '15 minutes',
        ];

        $tf15 = $this->tradesHistoryService->getVolumes($filter);

        $filter = [
            'symbol' => request('filter.symbol'),
            'tf' => '15 minutes',
        ];

        $tf4h = $this->tradesHistoryService->getVolumes($filter);

        return view('bybitdata::index', [
            'symbols' => $symbols,
            'tf15' => $tf15,
            'tf4h' => $tf4h,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('bybitdata::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('bybitdata::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('bybitdata::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
