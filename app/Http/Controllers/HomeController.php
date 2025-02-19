<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // redirect user to controls list
        if (Auth::User()->role === 5) {
            return redirect('/bob/index');
        }

        // count active domains
        $active_domains_count = DB::table('controls')
            ->select(
                'domain_id',
                DB::raw('max(controls.id)')
            )
            ->whereNull('realisation_date')
            ->groupBy('domain_id')
            ->get()
            ->count();

        // count all measures
        $controls_count = DB::table('measures')
            ->count();

        // count active controls
        $active_measures_count = DB::table('controls')
            ->whereNull('realisation_date')
            ->count();

        // count controls made
        $controls_made_count = DB::table('controls')
            ->whereNotNull('realisation_date')
            ->count();

        // count control never made
        $controls_never_made = DB::select(
            '
            select domain_id
            from controls c1
            where realisation_date is null and
            not exists (
                select *
                from controls c2
                where c2.next_id=c1.id);'
        );

        // Last controls made by measures
        $active_controls =
        DB::table('controls as c1')
            ->select(['c1.id', 'c1.measure_id', 'domains.title', 'c1.realisation_date', 'c1.score'])
            ->join('controls as c2', 'c2.id', '=', 'c1.next_id')
            ->join('domains', 'domains.id', '=', 'c1.domain_id')
            ->whereNull('c2.realisation_date')
            ->orderBy('c1.id')
            ->get();
        // dd($active_controls);

        // Get controls todo
        $controls_todo =
        DB::table('controls as c1')
            ->select([
                'c1.id',
                'c1.measure_id',
                'c1.name',
                'c1.scope',
                'c1.clause',
                'c1.domain_id',
                'c1.plan_date',
                'c2.id as prev_id',
                'c2.realisation_date as prev_date',
                'c2.score as score',
                'domains.title as domain',
            ])
            ->leftjoin('controls as c2', 'c1.id', '=', 'c2.next_id')
            ->join('domains', 'domains.id', '=', 'c1.domain_id')
            ->whereNull('c1.realisation_date')
            ->where('c1.plan_date', '<', Carbon::today()->addDays(30)->format('Y-m-d'))
            ->orderBy('c1.plan_date')
            ->get();
        // dd($plannedMeasurements);

        // planed controls this month
        $planed_controls_this_month_count = DB::table('controls')
            ->where(
                [
                    ['realisation_date','=',null],
                    ['plan_date','>=', (new Carbon('first day of this month'))->toDateString()],
                    ['plan_date','<', (new Carbon('first day of next month'))->toDateString()],
                ]
            )
            ->count();
        $request->session()->put('planed_controls_this_month_count', $planed_controls_this_month_count);

        // late controls
        $late_controls_count = DB::table('controls')
            ->where(
                [
                    ['realisation_date','=',null],
                    ['plan_date','<', Carbon::today()->format('Y-m-d')],
                ]
            )
            ->count();
        $request->session()->put('late_controls_count', $late_controls_count);

        // Count number of action plans
        $action_plans_count =
                DB::table('controls as c1')
                    ->leftjoin('controls as c2', 'c1.id', '=', 'c2.next_id')
                    ->whereNull('c1.realisation_date')
                    ->where(function ($query) {
                        return $query
                            ->where('c2.score', '=', 1)
                            ->orWhere('c2.score', '=', 2);
                    })
                    ->count();

        $request->session()->put('action_plans_count', $action_plans_count);

        // Get all controls
        $controls = DB::table('controls')
            ->select(['id', 'clause', 'score', 'realisation_date', 'plan_date'])
            ->get();

        // return
        return view('welcome')
            ->with('active_domains_count', $active_domains_count)
            ->with('active_controls', $active_controls)
            ->with('controls_count', $controls_count)
            ->with('active_measures_count', $active_measures_count)
            ->with('controls_made_count', $controls_made_count)
            ->with('controls_never_made', $controls_never_made)

            ->with('controls_todo', $controls_todo)
            ->with('active_controls', $active_controls)
            ->with('action_plans_count', $action_plans_count)
            ->with('late_controls_count', $late_controls_count)

            ->with('controls', $controls)
        ;
    }

    public function test(Request $request) {

        $domain = DB::table('domains')->first();

        return view('test')
            ->with('domain',$domain);
    }

}
