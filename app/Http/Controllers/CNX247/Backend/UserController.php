<?php

namespace App\Http\Controllers\CNX247\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Paystack;
use App\User;
use App\Notification;
use App\Resignation;
use App\Clocker;
use App\Qualification;
use App\Education;
use App\PlanFeature;
use App\QueryEmployee;
use App\EmployeeAppraisal;
use App\ModuleManager;
use App\IdeaBox;
use Auth;
use Image;
use DB;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /*
    * User profile
    */
    public function myProfile(){
        return view('backend.user.my-profile');
    }

    /*
    * View all your notifications
    */
    public function notifications(){
        $notifications = Auth::user()->unReadNotifications;
        return view('backend.user.all-notifications', ['unread'=>$notifications]);
    }

    /*
    * Upload avatar
    */
    public function uploadAvatar(Request $request){
        $this->validate($request,[
            'avatar'=>'required'
        ]);
        if($request->avatar){
    	    $file_name = time().'.'.explode('/', explode(':', substr($request->avatar, 0, strpos($request->avatar, ';')))[1])[1];
    	    //avatar image
    	    \Image::make($request->avatar)->resize(100, 100)->save(public_path('assets/images/avatars/medium/').$file_name);
    	    \Image::make($request->avatar)->resize(80, 80)->save(public_path('assets/images/avatars/thumbnails/').$file_name);


    	}
        $user = User::find(Auth::user()->id);
        $user->avatar = $file_name;
        $user->save();
        return response()->json(['message'=>'Success! Profile picture set.']);
    }
    /*
    * Upload cover photo
    */
    public function uploadCoverPhoto(Request $request){
        $this->validate($request,[
            'cover'=>'required'
        ]);
        if($request->cover){
    	    $file_name = time().'.'.explode('/', explode(':', substr($request->cover, 0, strpos($request->cover, ';')))[1])[1];
    	    //cover image
    	    \Image::make($request->cover)->resize(1207, 217)->save(public_path('assets/images/cover-photos/').$file_name);


    	}
        $user = User::find(Auth::user()->id);
        $user->cover = $file_name;
        $user->save();
        return response()->json(['message'=>'Success! Cover photo set.']);
    }

    /*
    * User administrative report
    */
    public function administration(){
        $resignations = Resignation::where('user_id', Auth::user()->id)->where('tenant_id',Auth::user()->tenant_id)->get();
        $attendance = Clocker::where('user_id', Auth::user()->id)->where('tenant_id',Auth::user()->tenant_id)->get();
        $queries = QueryEmployee::where('user_id', Auth::user()->id)->where('tenant_id',Auth::user()->tenant_id)->get();
        $myAppraisals = EmployeeAppraisal::where('employee', Auth::user()->id)
                                            ->where('tenant_id',Auth::user()->tenant_id)
                                            ->get();
        $supervisors = EmployeeAppraisal::where('supervisor', Auth::user()->id)
                                            ->where('tenant_id',Auth::user()->tenant_id)
                                            ->get();
        return view('backend.user.administration',[
            'resignations'=>$resignations,
            'attendance'=>$attendance,
            'queries'=>$queries,
            'myAppraisals'=>$myAppraisals,
            'supervisors'=>$supervisors
        ]);
    }
    /*
    * User settings
    */
    public function settings(){
         return view('backend.user.settings');
    }

    /*
    * User education
    */
    public function education(){
        $qualifications = Qualification::orderBy('name', 'ASC')->get();
         return view('backend.user.education', ['qualifications'=>$qualifications]);
    }

    /*
    * Save education
    */
    public function storeEducation(Request $request){
        $this->validate($request,[
            'institution_name.*'=>'required',
            'program.*'=>'required',
            'start_date.*'=>'required',
            'qualification.*'=>'required',
            'address.*'=>'required'
        ]);
        for($i = 0; $i<count($request->program); $i++){
            $edu = new Education;
            $edu->user_id = Auth::user()->id;
            $edu->tenant_id = Auth::user()->tenant_id;
            $edu->institution = $request->institution_name[$i];
            $edu->qualification_id = $request->qualification[$i];
            $edu->program = $request->program[$i];
            $edu->start_date = $request->start_date[$i];
            $edu->end_date = $request->end_date[$i];
            $edu->address = $request->address[$i];
            $edu->save();
        }
        session()->flash("success", "<strong>Success!</strong> Record saved.");
        return redirect()->back();
    }

    /*
    * work experience
    */
    public function workExperience(){
         return view('backend.user.work-experience');
    }

    public function ourPricing(){
        $plans = PlanFeature::orderBy('price', 'ASC')->get();
        return view('backend.user.our-pricing', ['plans'=>$plans]);
    }

    public function myIdeas(){
        $myIdeas = IdeaBox::where('tenant_id', Auth::user()->tenant_id)
                        ->where('user_id', Auth::user()->id)->get();
        return view('backend.user.my-ideas',['myIdeas'=>$myIdeas]);
    }
    public function submitIdea(Request $request){
        $this->validate($request,[
            'subject'=>'required',
            'content'=>'required',
            'visibility'=>'required'
        ]);
        $idea = new IdeaBox;
        $idea->tenant_id = Auth::user()->tenant_id;
        $idea->user_id = Auth::user()->id;
        $idea->content = $request->content;
        $idea->subject = $request->subject;
        $idea->visibility = $request->visibility;
        $idea->save();
        session()->flash("success", "<strong>Success!</strong> New idea submitted.");
        return redirect()->route('my-ideas');
    }

    public function renewMembership($timestamp, $plan){
        $chosen_plan = PlanFeature::where('slug', $plan)->first();
        $permissionObj = DB::table('role_has_permissions')
        ->select('permission_id')
        ->where('role_id', $chosen_plan->plan_id)
        ->distinct()
        ->get();
        $permissionIds = array();
        foreach ($permissionObj as $permit) {
            array_push($permissionIds,$permit->permission_id);
        }
        $moduleObj = Permission::select('module')->whereIn('id', $permissionIds)->distinct()->get();
        $moduleIds = array();
        foreach($moduleObj as $mod){
            array_push($moduleIds, $mod->module);
        }
        $modules = ModuleManager::whereIn('id', $moduleIds)->orderBy('module_name', 'ASC')->get();
        return view('backend.user.preview-membership',
        ['plan'=>$chosen_plan,
        'modules'=>$modules
        ]);
    }

    public function proceedToPay(Request $request){
          $this->validate($request,[
                  'email'=>'required',
                  'first_name'=>'required',
                  'amount'=>'required'
              ]);
              return Paystack::getAuthorizationUrl()->redirectNow();
      }

}
