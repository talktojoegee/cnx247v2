<?php

namespace App\Http\Livewire\Backend\Workflow;

use Livewire\Component;
use App\Post;
use App\PostComment;
use App\PostAttachment;
use App\PostRevision;
use App\RequestApprover;
use App\ResponsiblePerson;
use App\BusinessLog;
use Carbon\Carbon;
use App\Mail\RequisitionVerificationMail;
use App\RequisitionVerification;
use App\User;
use Auth;

class ViewWorkflowTask extends Component
{
    public $link;
    public $comment;
    public $likes;
    public $review;
    public $request;
    public $attachments;

    public $verificationCode;
    public $actionStatus = 0;
    public $verificationPostId;

    public function render()
    {
        return view('livewire.backend.workflow.view-workflow-task');
    }

    public function mount($url = ''){
        $this->link = request('url', $url);
        $this->getContent();
    }

    /*
    * Load content
    */
    public function getContent(){
        $this->request = Post::where('post_url', $this->link)->where('tenant_id',Auth::user()->tenant_id)->first();
        $this->attachments = PostAttachment::where('post_id', $this->request->id)
                            ->where('tenant_id',Auth::user()->tenant_id)
                            ->get();
    }

    /*
    * Comment on request
    */
    public function leaveCommentBtn($id){
        $this->validate([
            'id'=>'required',
            'comment'=>'required'
        ]);
        $com = new PostComment;
        $com->user_id = Auth::user()->id;
        $com->post_id = $id;
        $com->comment = $this->comment;
        $com->tenant_id = Auth::user()->tenant_id;
        $com->save();
        $this->comment = '';
        $this->getContent();
    }
    /*
    * Review request
    */
    public function leaveReviewBtn($id){
        $this->validate([
            'id'=>'required',
            'review'=>'required'
        ]);
        $com = new PostRevision;
        $com->user_id = Auth::user()->id;
        $com->post_id = $id;
        $com->content = $this->review;
        $com->tenant_id = Auth::user()->tenant_id;
        $com->save();
        $this->review = '';
        $this->getContent();
    }



     /*
    * Approve request
    */
    public function approveRequest($id){
        $now = Carbon::now()->format('Y-m-d H:i');
        $approve = ResponsiblePerson::where('post_id', $id)
                    ->where('user_id', Auth::user()->id)
                    ->where('tenant_id', Auth::user()->tenant_id)
                    ->first();
        $request = Post::where('tenant_id', Auth::user()->tenant_id)
                        ->where('id', $id)
                        ->first();
        if(!empty($approve) ){
            $code = strtoupper(substr(sha1(time()),32,40));
            $verify = new RequisitionVerification;
            $verify->post_id = $id;
            $verify->tenant_id = Auth::user()->tenant_id;
            $verify->processor_id = Auth::user()->id;
            $verify->expires = now(); //$now->addHours(2);
            $verify->code = $code;
            $verify->action = 'approved';
            $verify->save();
            #mail
            \Mail::to($approve->user->email)->send(new RequisitionVerificationMail($approve->user, $request, $code));
        }
        $this->actionStatus = 1;
        $this->verificationPostId = $id;
        session()->flash("success_code", "<strong>Success!</strong> We just sent verification code to your registered email.");

    }

    /*
    * Decline request
    */
    public function declineRequest($id){
            $now = Carbon::now()->format('Y-m-d H:i');
            $decline = ResponsiblePerson::where('post_id', $id)
                        ->where('user_id', Auth::user()->id)
                        ->where('tenant_id', Auth::user()->tenant_id)
                        ->first();
            $request = Post::where('tenant_id', Auth::user()->tenant_id)
                        ->where('id', $id)
                        ->first();
            if(!empty($decline) ){
                $code = strtoupper(substr(sha1(time()),32,40));
                $verify = new RequisitionVerification;
                $verify->post_id = $id;
                $verify->tenant_id = Auth::user()->tenant_id;
                $verify->processor_id = Auth::user()->id;
                $verify->expires = now(); //$now->addHours(2);
                $verify->code = $code;
                $verify->action = 'declined';
                $verify->save();
            }
            \Mail::to($decline->user->email)->send(new RequisitionVerificationMail($decline->user, $request, $code));
            $this->actionStatus = 1;
            $this->verificationPostId = $id;
            session()->flash("success_code", "<strong>Success!</strong> We just sent verification code to your registered email.");
    }

    public function clockIn($id){

    }
    public function verifyCode($id){
        $verify = RequisitionVerification::where('post_id', $id)
                    ->where('processor_id', Auth::user()->id)
                    ->where('tenant_id', Auth::user()->tenant_id)
                    ->where('status', 0)//in-progress
                    ->where('code', $this->verificationCode)//in-progress
                    ->first();
        if(!empty($verify) ){
            if($verify->code === $this->verificationCode){
                $details = Post::find($id);
                $verifyStatus = $verify->action;
                if($verifyStatus == 'approved'){
                    $action = ResponsiblePerson::where('post_id', $id)->where('user_id', Auth::user()->id)->first();
                    $action->status = $verifyStatus;
                    $action->save();
                    //Register business process log
                    $log = new BusinessLog;
                    $log->request_id = $id;
                    $log->user_id = Auth::user()->id;
                    $log->name = $verifyStatus;
                    $log->note = str_replace('-', ' ',$details->post_type)." ".$verifyStatus." by ".Auth::user()->first_name." ".Auth::user()->surname ?? " ";
                    $log->save();
                    $responsiblePersons = ResponsiblePerson::where('post_id', $id)
                                                //->where('user_id', Auth::user()->id)
                                                ->get();
                    $responsiblePersonIds = [];
                    foreach($responsiblePersons as $per){
                       array_push($responsiblePersonIds, $per->user_id);
                    }
                    //search for processor
                    $approvers = RequestApprover::where('request_type', $details->post_type)
                                                ->where('depart_id', $details->user->department_id)
                                                ->where('tenant_id', Auth::user()->tenant_id)
                                                ->get();
                    $approverIds = [];
                    if(!empty($approvers) ){
                        foreach($approvers as $approver){
                            array_push($approverIds, $approver->user_id);
                        }
                    }
                    $remainingProcessors = array_diff($approverIds,$responsiblePersonIds);
                    //identify next supervisor
                    $supervise = new BusinessLog;
                    $supervise->request_id = $id;
                    $supervise->user_id = Auth::user()->id;
                    $supervise->name = 'Log entry';
                    $supervise->note = "Identifying next processor for ".str_replace('-', ' ',$details->post_type).": ".$details->post_title;
                    $supervise->save();
                    //return dd($remainingProcessors);
                    //Assign next processor
                    if(!empty($remainingProcessors) ){
                        $reset = array_values($remainingProcessors);
                        for($i = 0; $i<count($reset); $i++){
                            $next = new ResponsiblePerson;
                            $next->post_id = $id;
                            $next->user_id = $reset[$i];
                            $next->tenant_id = Auth::user()->tenant_id;
                            $next->save();
                            $user = User::find($reset[$i]);
                            $user->notify(new NewPostNotification($details));
                        break;
                        }
                    }else{
                        $status = Post::find($id);
                        $status->post_status = $verifyStatus;
                        $status->save();
                        #Requisition to GL flow takes over from here
                    }
                    $this->actionStatus = 0;
                    $this->verificationPostId = null;
                    $this->getContent();
                    session()->flash("done", "<p class='text-success text-center'>Request verified successfully.</p>");
                }else{
                    $action = ResponsiblePerson::where('post_id', $id)->where('user_id', Auth::user()->id)->first();
                    $action->status = $verifyStatus;
                    $action->save();
                    //Register business process log
                    $log = new BusinessLog;
                    $log->request_id = $id;
                    $log->user_id = Auth::user()->id;
                    $log->name = $verifyStatus;
                    $log->note = str_replace('-', ' ',$details->post_type)." ".$verifyStatus." by ".Auth::user()->first_name." ".Auth::user()->surname;
                    $log->save();
                     //update request table finally
                     $status = Post::find($id);
                     $status->post_status = $verifyStatus;
                     $status->save();
                        $this->actionStatus = 0;
                        $this->verificationPostId = null;
                        $this->getContent();
                        session()->flash("done", "<p class='text-success text-center'>Request verified successfully.</p>");
                }
            }else{
                session()->flash("error_code", "<strong>Ooops!</strong>  Authentication code mis-match. Try again.");
            }
        }else{
            session()->flash("error_code", "<strong>Ooops!</strong> There's no authentication code for this request.");
        }

    }
}
