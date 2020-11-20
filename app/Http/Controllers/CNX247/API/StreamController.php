<?php

namespace App\Http\Controllers\CNX247\API;

use App\BusinessLog;
use App\Http\Controllers\Controller;
use App\Notifications\NewPostNotification;
use App\Observer;
use App\Participant;
use App\Post;
use App\PostAttachment;
use App\PostComment;
use App\PostLike;
use App\Priority;
use App\RequestApprover;
use App\ResponsiblePerson;
use App\User;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    //
    public function index(Request $request)
    {

        $tenant_id = $request->input("tenant_id");

        $allposts = array();
        $posts = Post::where('posts.tenant_id', $tenant_id)->orderBy('id', 'DESC')->get();

        foreach ($posts as $post) {
            $postArray = array();
            $user = User::where('id', $post->user_id)->get();

            /* parse profile picture */
            $user[0]["avatar"] = url("/assets/images/avatars/thumbnails/" . $user[0]["avatar"]);

            /* parse comments */
            //$comments =
            //$post->postComments;//
            $comments = PostComment::where('post_id', $post->id)->join('users', 'users.id', 'post_comments.user_id')->orderBy('post_comments.id', 'DESC')->get();
            foreach ($comments as $comment) {
                $comment['avatar'] = url("/assets/images/avatars/thumbnails/" . $comment['avatar']);
            }
            $post['comments'] = $comments;

            /* parse post likes */

            $postLikes = PostLike::where("post_id", $post->id)->join('users', 'post_likes.user_id', '=', 'users.id')->get();
            $post['likes'] = count($postLikes);
            $post['post_likes'] = $postLikes;
						$post['posted'] = date('M j , Y', strtotime($post->created_at));
            $responsible = ResponsiblePerson::where('post_id', $post->id)->join('users', 'responsible_people.user_id', '=', 'users.id')->get();
            $attachments = PostAttachment::where('post_id', $post->id)->get();

            /* Parse Attachments */
            foreach ($attachments as $attachment) {
                $attachment["attachment"] = url("/assets/uploads/attachments/" . $attachment['attachment']);
            }

            $postArray["user"] = $user;
            $postArray['post'] = $post;
            $postArray['responsible'] = $responsible;
            $postArray['attachments'] = $attachments;
            $allposts[] = $postArray;
            //return response()->json(['posts' =>$post], 500);
        }

        return response()->json(['posts' => $allposts,
        ], 500);

    }

    public function StreamPost(Request $request)
    {

        $tenant_id = $request->input("tenant_id");
        $post_id = $request->input("post_id");

        $allposts = array();
        $posts = Post::where('posts.tenant_id', $tenant_id)->where('posts.id', $post_id)->get();

        foreach ($posts as $post) {
            $postArray = array();
            $user = User::where('id', $post->user_id)->get();

            /* parse profile picture */
            $user[0]["avatar"] = url("/assets/images/avatars/thumbnails/" . $user[0]["avatar"]);

            /* parse comments */
            //$comments =
            //$post->postComments;//
            $comments = PostComment::where('post_id', $post->id)->join('users', 'users.id', 'post_comments.user_id')->orderBy('post_comments.id', 'DESC')->get();
            foreach ($comments as $comment) {
                $comment['avatar'] = url("/assets/images/avatars/thumbnails/" . $comment['avatar']);
            }
            $post['comments'] = $comments;

            /* parse post likes */

            $postLikes = PostLike::where("post_id", $post->id)->join('users', 'post_likes.user_id', '=', 'users.id')->get();
            $post['likes'] = count($postLikes);
            $post['post_likes'] = $postLikes;
            $post['posted'] = date('M j , Y', strtotime($post->created_at));
            $responsible = ResponsiblePerson::where('post_id', $post->id)->join('users', 'responsible_people.user_id', '=', 'users.id')->get();
            $attachments = PostAttachment::where('post_id', $post->id)->get();

            /* Parse Attachments */
            foreach ($attachments as $attachment) {
                $attachment["attachment"] = url("/assets/uploads/attachments/" . $attachment['attachment']);
            }

            $postArray["user"] = $user;
            $postArray['post'] = $post;
            $postArray['responsible'] = $responsible;
            $postArray['attachments'] = $attachments;
            $allposts[] = $postArray;
            //return response()->json(['posts' =>$post], 500);
        }

        return response()->json(['posts' => $allposts,
        ], 500);

    }

    public function like(Request $request)
    {
        $user_id = $request->input("user_id");
        $tenant_id = $request->input("tenant_id");
        $post_id = $request->input("post_id");

        $like = new PostLike();
        $like->post_id = $post_id;
        $like->user_id = $user_id;
        $like->tenant_id = $tenant_id;
        $like->save();
        return response()->json(['status' => 200]);
    }

    public function comment(Request $request)
    {
        $user_id = $request->input("user_id");
        $tenant_id = $request->input("tenant_id");
        $post_id = $request->input("post_id");
        $comment = $request->input("comment");

        $com = new PostComment;
        $com->user_id = $user_id;
        $com->post_id = $post_id;
        $com->comment = $comment;
        $com->tenant_id = $tenant_id;
        $com->save();
        return response()->json(['status' => 200]);
    }

    public function storeTask(Request $request)
    {
        $darray = array();
        $url = substr(sha1(time()), 10, 10);
        $task = new Post;
        $task->post_title = $request->task_title;
        $task->user_id = $request->user_id;
        $task->post_content = $request->task_description;
        //$task->post_color = $request->color;
        $task->post_type = 'task';
        $task->post_url = $url;
        $task->start_date = $request->start_date ?? '';
        $task->end_date = $request->due_date;
        $task->post_priority = $request->priority;
        $task->tenant_id = $request->tenant_id;
        $task->save();
        $task_id = $task->id;

        //Attachment
        if (!empty($request->attachment)) {
            $attach = new PostAttachment;
            $attach->post_id = $task_id;
            $attach->user_id = $request->user_id;
            $attach->attachment = $request->attachment;
            $attach->tenant_id = $request->tenant_id;
            $attach->save();
        }

        //responsible persons
        if (!empty($request->persons)) {
            foreach ($request->persons as $person) {

                $part = new ResponsiblePerson;
                $part->post_id = $task_id;
                $part->post_type = 'task';
                $part->user_id = $person["id"];
                $part->tenant_id = $request->tenant_id;
                $part->save();
                $darray["persons"][] = $person["id"];
                $user = User::find($person);
                //$user->notify(new NewPostNotification($task));
            }
        }
        //participants
        if (!empty($request->participants)) {
            foreach ($request->participants as $participant) {
                /*  $user = User::select('first_name', 'surname', 'email', 'id')->where('id', $participant)->first();
                \Mail::to($user->email)->send(new MailTask($user, $request, $url)); */
                $part = new Participant;
                $part->post_id = $task_id;
                $part->post_type = 'task';
                $part->user_id = $participant["id"];
                $part->tenant_id = $request->tenant_id;
                $part->save();
                $darray["participants"][] = $participant["id"];
            }
        }
        //observers
        if (!empty($request->observers)) {
            foreach ($request->observers as $observer) {
                /*  $user = User::select('first_name', 'surname', 'email', 'id')->where('id', $participant)->first();
                \Mail::to($user->email)->send(new MailTask($user, $request, $url)); */
                $part = new Observer;
                $part->post_id = $task_id;
                $part->post_type = 'task';
                $part->user_id = $observer["id"];
                $part->tenant_id = $request->tenant_id;
                $part->save();
                $darray["observes"][] = $observer["id"];

            }
        }
        return response()->json(['message' => 'Success! Task created.', "parsed" => $darray], 200);
    }

    public function storeProject(Request $request)
    {
        $url = substr(sha1(time()), 10, 10);
        $project = new Post;
        $project->post_title = $request->project_title;
        $project->user_id = $request->user_id;
        $project->post_content = $request->project_description;
        $project->post_color = $request->color;
        $project->project_manager_id = $request->project_manager;
        $project->post_type = 'project';
        $project->post_url = $url;
        //$project->budget = $request->budget ?? '';
        $project->sponsor = $request->project_sponsor;
        $project->start_date = $request->start_date ?? '';
        $project->end_date = $request->due_date;
        $project->post_priority = $request->priority;
        $project->tenant_id = $request->tenant_id;
        //$task->attachment = $filename;
        $project->save();
        $project_id = $project->id;

        //Attachment
        if (!empty($request->attachment)) {
            $attach = new PostAttachment;
            $attach->post_id = $project_id;
            $attach->user_id = $request->user_id;
            $attach->attachment = $request->attachment;
            $attach->tenant_id = $request->tenant_id;
            $attach->save();
        }

        //responsible persons
        if (!empty($request->persons)) {
            foreach ($request->persons as $person) {

                $part = new ResponsiblePerson;
                $part->post_id = $project_id;
                $part->post_type = 'project';
                $part->user_id = $person["id"];
                $part->tenant_id = $request->tenant_id;
                $part->save();
                $darray["persons"][] = $person["id"];
                $user = User::find($person);
                //$user->notify(new NewPostNotification($task));
            }
        }
        //participants
        if (!empty($request->participants)) {
            foreach ($request->participants as $participant) {
                /*  $user = User::select('first_name', 'surname', 'email', 'id')->where('id', $participant)->first();
                \Mail::to($user->email)->send(new MailTask($user, $request, $url)); */
                $part = new Participant;
                $part->post_id = $project_id;
                $part->post_type = 'project';
                $part->user_id = $participant["id"];
                $part->tenant_id = $request->tenant_id;
                $part->save();
                $darray["participants"][] = $participant["id"];
            }
        }
        //observers
        if (!empty($request->observers)) {
            foreach ($request->observers as $observer) {
                /*  $user = User::select('first_name', 'surname', 'email', 'id')->where('id', $participant)->first();
                \Mail::to($user->email)->send(new MailTask($user, $request, $url)); */
                $part = new Observer;
                $part->post_id = $project_id;
                $part->post_type = 'project';
                $part->user_id = $observer["id"];
                $part->tenant_id = $request->tenant_id;
                $part->save();
                $darray["observes"][] = $observer["id"];

            }
        }

        return response()->json(['message' => 'Success! Task created.'], 200);
    }

    public function storeAnnouncement(Request $request)
    {

        $url = substr(sha1(time()), 10, 10);
        $announcement = new Post;
        $announcement->post_title = $request->subject;
        $announcement->user_id = $request->user_id;
        $announcement->tenant_id = $request->tenant_id;
        $announcement->post_content = $request->description;
        $announcement->post_type = 'announcement';
        $announcement->post_url = $url;
        $announcement->save();
        $announcement_id = $announcement->id;

        //notify
        $user = $announcement->user;
        $user->notify(new NewPostNotification($announcement));

        //save attachment
        if (!empty($request->attachment)) {
            $attach = new PostAttachment;
            $attach->post_id = $announcement_id;
            $attach->user_id = $request->user_id;
            $attach->tenant_id = $request->tenant_id;
            $attach->attachment = $request->attachment;
            $attach->save();
        }
        //responsible persons
        if ($request->recipient == 0) {
            $part = new ResponsiblePerson;
            $part->post_id = $announcement_id;
            $part->post_type = 'announcement';
            $part->user_id = 32;
            $part->tenant_id = $request->tenant_id;
            $part->save();
        } else {
            if (!empty($request->persons)) {

                foreach ($request->persons as $person) {

                    /*  $user = User::select('first_name', 'surname', 'email', 'id')->where('id', $participant)->first();
                    \Mail::to($user->email)->send(new MailTask($user, $request, $url)); */
                    $part = new ResponsiblePerson;
                    $part->post_id = $announcement_id;
                    $part->post_type = 'announcement';
                    $part->user_id = $person["id"];
                    $part->tenant_id = $request->tenant_id;
                    $part->save();
                    //send notification
                    //    $user = User::find($person);
                    //    $user->notify(new NewPostNotification($announcement));
                }
            }
        }
        if ($announcement) {
            return response()->json(['message' => 'Success!'], 200);
        } else {
            return response()->json(['error' => 'Ooops! Something went wrong. Try again.'], 400);
        }
    }

    public function storeEvent(Request $request)
    {

        $url = substr(sha1(time()), 10, 10);
        $event = new Post;
        $event->post_title = $request->subject;
        $event->user_id = $request->user_id;
        $event->post_content = $request->description;
        $event->post_type = 'event';
        $event->post_url = $url;
        $event->tenant_id = $request->tenant_id;
        $event->start_date = $request->start_date ?? '';
        $event->end_date = $request->due_date ?? '';
        $event->save();
        $event_id = $event->id;
        //send notification
        $user = $event->user;
        $user->notify(new NewPostNotification($event));

        //Attachment
        if (!empty($request->attachment)) {
            $attach = new PostAttachment;
            $attach->post_id = $event_id;
            $attach->user_id = $request->user_id;
            $attach->attachment = $request->attachment;
            $attach->tenant_id = $request->tenant_id;
            $attach->save();
        }

        //responsible persons
        if ($request->recipient == 0) {
            $part = new ResponsiblePerson;
            $part->post_id = $event_id;
            $part->post_type = 'event';
            $part->user_id = 32;
            $part->tenant_id = $request->tenant_id;
            $part->save();
        } else {
            if (!empty($request->persons)) {
                foreach ($request->persons as $person) {
                    /*  $user = User::select('first_name', 'surname', 'email', 'id')->where('id', $participant)->first();
                    \Mail::to($user->email)->send(new MailTask($user, $request, $url)); */
                    $part = new ResponsiblePerson;
                    $part->post_id = $event_id;
                    $part->post_type = 'event';
                    $part->user_id = $person["id"];
                    $part->tenant_id = $request->tenant_id;
                    $part->save();
                    //send notification
                    $user = User::find($person);
                    $user->notify(new NewPostNotification($event));
                }
            }
        }
        if ($event) {
            return response()->json(['message' => 'Success! Event registered.'], 200);
        } else {
            return response()->json(['error' => 'Success! Ooops! Something went wrong. Try again.'], 400);

        }
    }

    public function storeReport(Request $request)
    {

        $department_id = $request->department_id;
        $tenant_id = $request->tenant_id;
				$reporttype =  $request->type;
        $processor = RequestApprover::select('user_id')
            ->where('request_type', 'expense-report')
            ->where('depart_id', $department_id)
            ->where('tenant_id', $tenant_id)
            ->first();
        if (empty($processor)) {
            return; //response()->json(["error"=>"Error! Could not submit. No processor found."],400);
        } else {

            $url = substr(sha1(time()), 10, 10);
            $expense = new Post;
            $expense->post_title = $request->subject;
            $expense->budget = $request->amount;
            $expense->currency = $request->currency;
            $expense->post_type = $reporttype;//'expense-report';
            $expense->post_content = $request->description;
            $expense->post_status = 'in-progress';
            $expense->user_id = $request->user_id;
            $expense->tenant_id = $request->tenant_id;
            $expense->post_url = $url;

            $expense->save();
            $id = $expense->id;

            //Attachment
            if (!empty($request->attachment)) {
                $attach = new PostAttachment;
                $attach->post_id = $id;
                $attach->user_id = $request->user_id;
                $attach->attachment = $request->attachment;
                $attach->tenant_id = $request->tenant_id;
                $attach->save();
            }

            $event = new ResponsiblePerson;
            $event->post_id = $id;
            $event->post_type = 'expense-report';
            $event->user_id = $processor->user_id;
            $event->tenant_id = $request->user_id;
            $event->save();
            $user = User::find($processor->user_id);
            $user->notify(new NewPostNotification($expense));

            //Register business process log
            $log = new BusinessLog;
            $log->request_id = $id;
            $log->user_id = $request->user_id;
            $log->note = "Approval for expense report " . $request->subject . " registered.";
            $log->name = "Registering expense report";
            $log->tenant_id = $request->tenant_id;
            $log->save();

            //identify supervisor
            $supervise = new BusinessLog;
            $supervise->request_id = $id;
            $supervise->user_id = $request->user_id;
            $supervise->name = "Log entry";
            $supervise->note = "Identifying processor for " . $request->first_name . " " . $request->surname;
            $supervise->tenant_id = $request->tenant_id;
            $supervise->save();


            return response()->json(['message' => 'Success! Expense report submitted.']);

        }
    }

    /*
     * Share file within the activity stream
     */
    public function shareFile(Request $request)
    {

        $url = substr(sha1(time()), 10, 10);
        $file = new Post;
        $file->post_title = $request->file_name;
        $file->user_id = $request->user_id;
        $file->tenant_id = $request->tenant_id;
        $file->post_content = $request->first_name . ' ' . $request->surname . ' shared a file titled <strong>' . $request->file_name . ' </strong>.';
        $file->post_type = 'file';
        $file->post_url = $url;
        $file->save();
        $file_id = $file->id;
        //Attachment
        if (!empty($request->attachment)) {
            $attach = new PostAttachment;
            $attach->post_id = $file_id;
            $attach->user_id = $request->user_id;
            $attach->attachment = $request->attachment;
            $attach->tenant_id = $request->tenant_id;
            $attach->save();
        }
        //responsible persons
        if ($request->recipient == 0) {
            $part = new ResponsiblePerson;
            $part->post_id = $file_id;
            $part->post_type = 'file';
            $part->user_id = 32;
            $part->tenant_id = $request->tenant_id;
            $part->save();
        } else {

            if (!empty($request->persons)) {
                foreach ($request->persons as $person) {
                    $part = new ResponsiblePerson;
                    $part->post_id = $file_id;
                    $part->post_type = 'file';
                    $part->user_id = $person["id"];
                    $part->tenant_id = $request->tenant_id;
                    $part->save();
                    //send notification
                    //$user = User::find($person);
                    //$user->notify(new NewPostNotification($file));
                }
            }
            $user = User::find($person);
            $user->notify(new NewPostNotification($file));
        }

        if ($file) {
            return response()->json(['message' => 'Success! File shared.'], 200);
        } else {
            return response()->json(['error' => 'Ooops! Something went wrong. Try again.'], 400);
        }
    }

    public function priorities()
    {
        $priorites = Priority::all();
        return response()->json(['priorities' => $priorites], 500);
    }

    public function uploadReport(Request $request)
    {

        if (!empty($request->file('attachment'))) {
            $extension = $request->file('attachment');
            $extension = $request->file('attachment')->getClientOriginalExtension();
            $size = $request->file('attachment')->getSize();
            $dir = 'assets/uploads/requisition/';
            $filename = uniqid() . '_' . time() . '_' . date('Ymd') . '.' . $extension;
            $request->file('attachment')->move(public_path($dir), $filename);
            return response()->json(['Response' => $filename], 200);
        } else {
            $filename = '';
            return response()->json(['Response' => ""], 204);
        }

    }

    public function upload(Request $request)
    {
        if (!empty($request->file('attachment'))) {
            $extension = $request->file('attachment');
            $extension = $request->file('attachment')->getClientOriginalExtension(); // getting excel extension
            $dir = 'assets/uploads/attachments/';
            $filename = 'task_' . uniqid() . '_' . time() . '_' . date('Ymd') . '.' . $extension;
            $request->file('attachment')->move(public_path($dir), $filename);
            return response()->json(['Response' => $filename], 200);
        } else {
            $filename = '';
            return response()->json(['Response' => ""], 204);
        }

    }

    public function projectUpload(Request $request)
    {
        if (!empty($request->file('attachment'))) {
            $extension = $request->file('attachment');
            $extension = $request->file('attachment')->getClientOriginalExtension(); // getting excel extension
            $dir = 'assets/uploads/attachments/';
            $filename = 'project_' . uniqid() . '_' . time() . '_' . date('Ymd') . '.' . $extension;
            $request->file('attachment')->move(public_path($dir), $filename);
            return response()->json(['Response' => $filename], 200);
        } else {
            $filename = '';
            return response()->json(['Response' => ""], 204);
        }

    }

}
