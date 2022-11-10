<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Settings;
use App\User;
use App\Rules\MatchOldPassword;
use Hash;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
class AdminController extends Controller
{
    public function index(){
        $qSyntax1 = \DB::raw("COUNT(*) as count");
        $qSyntax2 = \DB::raw("DAYNAME(created_at) as day_name");
        $qSyntax3 = \DB::raw("DAY(created_at) as day");
        $data = User::select($qSyntax1, $qSyntax2, $qSyntax3)
        ->where('created_at', '>', Carbon::today()->subDay(6))
        ->groupBy('day_name','day')
        ->orderBy('day')
        ->get();
     $array[] = ['Name', 'Number'];
     foreach($data as $key => $value)
     {
       $array[++$key] = [$value->day_name, $value->count];
     }
     return view('backend.index')->with('users', json_encode($array));
    }

    public function profile(){
        $profile=Auth()->user();
        return view('backend.users.profile')->with('profile',$profile);
    }

    public function profileUpdate(Request $request,$id){
        $user=User::findOrFail($id);
        $data=$request->all();
        $status=$user->fill($data)->save();
        if($status){
            request()->session()->flash('success','Successfully updated your profile');
        }
        else{
            request()->session()->flash('error','Please try again!');
        }
        return redirect()->back();
    }

    public function settings(){
        $data=Settings::first();
        return view('backend.setting')->with('data',$data);
    }

    public function settingsUpdate(Request $request){
        $requiredString = 'required|string';
        $this->validate($request,[
            'short_des'=>$requiredString,
            'description'=>$requiredString,
            'photo'=>'required',
            'logo'=>'required',
            'address'=>$requiredString,
            'email'=>'required|email',
            'phone'=>$requiredString,
        ]);
        $data=$request->all();
        $settings=Settings::first();
        $status=$settings->fill($data)->save();
        if($status){
            request()->session()->flash('success','Setting successfully updated');
        }
        else{
            request()->session()->flash('error','Please try again');
        }
        return redirect()->route('admin');
    }

    public function changePassword(){
        return view('backend.layouts.changePassword');
    }
    public function changPasswordStore(Request $request)
    {
        $request->validate([
            'current_password' => ['required', new MatchOldPassword],
            'new_password' => ['required'],
            'new_confirm_password' => ['same:new_password'],
        ]);

        User::find(auth()->user()->id)->update(['password'=> Hash::make($request->new_password)]);

        return redirect()->route('admin')->with('success','Password successfully changed');
    }

    // Pie chart
    public function userPieChart(Request $request){
        $qSyntax1 = \DB::raw("COUNT(*) as count");
        $qSyntax2 = \DB::raw("DAYNAME(created_at) as day_name");
        $qSyntax3 = \DB::raw("DAY(created_at) as day");
        $data = User::select($qSyntax1, $qSyntax2, $qSyntax3)
        ->where('created_at', '>', Carbon::today()->subDay(6))
        ->groupBy('day_name','day')
        ->orderBy('day')
        ->get();
     $array[] = ['Name', 'Number'];
     foreach($data as $key => $value)
     {
       $array[++$key] = [$value->day_name, $value->count];
     }
     return view('backend.index')->with('course', json_encode($array));
    }

}
