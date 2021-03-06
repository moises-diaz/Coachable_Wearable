<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use App\User;
use App\Event;
use App\Run;
use App\ParentAthlete;
use App\UserTeam;
use App\Team;

use App\Charts;
use App\Charts\RunChart;

use Illuminate\Support\Facades\Redirect;


class EventController extends Controller
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

    public function index($eventid, $userid)
    {
        if ($eventid <= 0 || $userid <= 0)
        {
            return Redirect::back()->withErrors(['msg', 'Invalid event id or user id']);
        }

        $id = Auth::id();
        
        $parents = ParentAthlete::where('athlete_id', $userid)->get();
        $isParent = false;
        foreach($parents as $parent)
        {
            if ($id == $parent->parent_id)
            {
                $isParent = true;
                break;
            }
        }
        
        $userTeam = UserTeam::where('user_id', $userid)->first();
        $team = Team::where('id', $userTeam->team_id)->first();
        $coaches = User::where('user_type_id', 3)->get();
        $isCoach = false;
        foreach($coaches as $coach)
        {
            $coachTeam = UserTeam::where('user_id', $coach->id)->first();
            if ($team->id == $coachTeam->team_id && $id == $coach->id)
            {
                $isCoach = true;
                break;
            }
        }
        
        if($userid != $id && !$isParent && !$isCoach)
        {
            return Redirect::back()->withErrors(['msg', 'Access Denied']);
        }
        else
        {
            $user = User::where('id', $userid)->first();
            $event = Event::where('id', $eventid)->first();
            $runs = Run::where(['user_id' => $userid, 'event_id' => $eventid])->get();
            
            if (count($runs) && $event)
            {
                $runData = array();
                
                foreach($runs as $run)
                {
                    $jsonObj = json_decode($run->other_data);
                    $timeArray = array();
                    $speedArray = array();
                    $altitudeArray = array();
                    
                    foreach($jsonObj as $dataEntry)
                    {
                        array_push($timeArray, $dataEntry->Time);
                        array_push($speedArray, $dataEntry->Speed);
                        array_push($altitudeArray, $dataEntry->Altitude);
                    }
                    
                    $temp = array();
                    array_push($temp, $run, $timeArray, $speedArray, $altitudeArray);
                    array_push($runData, $temp);
                }
                
                return view('event', compact('runData', 'event', 'user'));
            }
            else
            {
                return Redirect::back()->withErrors(['msg', 'Runs not in DB']);
            }
        }
    }
}
