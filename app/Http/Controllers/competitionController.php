<?php

namespace App\Http\Controllers;

use App\Helpers\ControllerHelper;
use App\Models\competition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\MultiException;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

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
        // try to decode request to json (in case of invalid JSON error is thrown)
        $content = json_decode($request->getContent(), false, JSON_THROW_ON_ERROR);

        // make sure content is always an array
        ControllerHelper::makeToArray($content);

        // empty arrays for storing the exceptions and competitions produced during the dry run
        $exceptions = [];
        $competitions = [];

        // check for errors in provided competitions
        foreach ($content as $input_competition) {
            // we need to convert the date from timestamp to Carbon Object (only if it exists)
            if (!empty($input_competition->date)) {
                try {
                    // try to generate Carbon object form timestamp (in case an exception is thrown catch it, add it to the list and continue)
                    $input_competition->date = Carbon::parse($input_competition->date); // note types are changed
                } catch (\Throwable $th) {
                    $exceptions[] = $th;
                    continue; // continue with next element in list
                }
            }

            // converting the integers
            if (!empty($input_competition->feature_set)) {
                if (($input_competition->feature_set = filter_var($input_competition->feature_set, FILTER_VALIDATE_INT)) === false) { // note: typed comparison mandatory
                    $exceptions[] = new InvalidFormatException("could not parse 'feature_set' to int.");
                    continue;
                }
            }
            if (!empty($input_competition->areas)) {
                if (($input_competition->areas = filter_var($input_competition->areas, FILTER_VALIDATE_INT)) === false) {
                    $exceptions[] = new InvalidFormatException("could not parse 'areas' to int.");
                    continue;
                }
            }

            // converting live to boolean (no exception handling since the method used above doesn't work and there hasn't been any interest in doing the required research to implement it correctly)
            if (!empty($input_competition->live))
                $input_competition->live = filter_var($input_competition->live, FILTER_VALIDATE_BOOLEAN);

            // TODO a competition might also be identified by it's name and date (prevent duplicates)
            // TODO add password protection

            // if an id has been provided search for the model in the database
            if (!empty($input_competition->id)) {
                try {
                    $competition = competition::findOrFail($input_competition->id);
                } catch (\Throwable $th) {
                    $exceptions[] = $th;
                    continue;
                }
            } else {
                $competition = new competition();
            }

            // now we fill the competition with the parsed data (we can safely overwrite id since we checked wether such an competition exists or not)
            $competition->fill((array) $input_competition);

            // add the competition to the list for saving (since nothing is written to the db if only one exception did occur, we can skip this step once we detected one)
            if (empty($exceptions))
                $competitions[] = $competition;
        }

        // check wether exceptions did occur if so throw an exception
        if (!empty($exceptions))
            throw new MultiException($exceptions);

        // no exceptions did occur we now write the competitions to the database
        foreach ($competitions as $competition) {
            $competition->save();
        }

        // return the save competitions
        return new JsonResponse($competitions);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        //
    }
}
