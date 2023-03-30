<?php

namespace App\Http\Controllers;

use App\Models\competition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class competitionController extends Controller
{
    /**
     * @var int The maximum of results per page
     */
    const RESULTS_LIMIT = 100;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        // get values form request -> to int -> to absolute value
        // note pages start counting at 1
        $id = abs(intval($request->input('id')));
        $page = abs(intval($request->input('page')));
        $limit  = abs(intval($request->input('limit')));

        // check if limit is to high if so reduce it to the given limit
        $limit = $limit > self::RESULTS_LIMIT ? self::RESULTS_LIMIT : $limit;
        // if limit is 0 set it to max
        $limit = $limit == 0 ? self::RESULTS_LIMIT : $limit;;

        // decide exactly what the user wants to get
        switch (true) {
            case $id: // id overrides all other parameters
                return new JsonResponse(competition::findOrFail($id));

            case $page: // if page is given (and not 0) assume pagination is desired
                // calculate the total number of pages
                $number_of_pages = ceil(competition::all()->count() * 1.0 / $limit);

                // check if page is larger than $number of pages if so set it to the last page
                $page = $page > $number_of_pages ? $number_of_pages : $page;

                // if a page is given return result with pagination
                $competitions = competition::orderByDesc('changed')->limit($limit)->offset(($page - 1) * $limit)->get();

                // return them as a json
                return new JsonResponse([$number_of_pages, $competitions]);

            default: // only return the first few competitions
                return new JsonResponse(competition::orderByDesc('changed')->limit($limit)->get());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upsert(Request $request): JsonResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        //
    }
}
