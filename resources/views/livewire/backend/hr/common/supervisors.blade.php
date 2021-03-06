<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="sub-title">{{$supervisorId == 0 ? 'Add New Supervisor' : 'Edit Supervisor'}}</h5>
            </div>
            <div class="card-block">
                <form wire:submit.prevent="submitSupervisor">
                    @if (session()->has('success'))
                        <div class="alert alert-success background-success mt-3">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <i class="icofont icofont-close-line-circled text-white"></i>
                            </button>
                            {!! session()->get('success') !!}
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="alert alert-danger background-danger mt-3">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <i class="icofont icofont-close-line-circled text-white"></i>
                            </button>
                            {!! session()->get('error') !!}
                        </div>
                    @endif
                    <div class="form-group">
                        <label for="">Department</label>
                        <select wire:model.lazy="department" class="form-control">
                            <option disabled selected>Select department</option>
                            @foreach ($departments as $department)
                                <option value="{{$department->id}}">{{$department->department_name ?? '' }}</option>
                            @endforeach
                        </select>
                        @error('department')
                            <i class="text-danger mt-2">{{$message}}</i>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="">Employee</label>
                        <select wire:model.lazy="supervisor" class="form-control">
                            <option disabled selected>Select supervisor</option>
                            @foreach ($employees as $employee)
                                <option value="{{$employee->id}}">{{$employee->first_name }} {{$employee->surname ?? ''}}</option>
                            @endforeach
                        </select>
                        @error('department')
                            <i class="text-danger mt-2">{{$message}}</i>
                        @enderror
                    </div>
                    @if($supervisorId == 0)
                        <div class="form-group d-flex justify-content-center">
                            <button class="btn btn-mini btn-primary" type="submit"> <i class="ti-check"></i> Submit </button>
                            <div class="preloader3 loader-block" wire:loading wire.target="submitSupervisor">
                                <div class="circ1 loader-primary"></div>
                                <div class="circ2 loader-primary"></div>
                                <div class="circ3 loader-primary"></div>
                                <div class="circ4 loader-primary"></div>
                            </div>
                        </div>
                    @elseif($supervisorId != 0)
                        <div class="form-group d-flex justify-content-center">
                            <button class="btn btn-mini btn-primary" type="submit"> <i class="ti-check"></i> Save Changes</button>
                            <div class="preloader3 loader-block" wire:loading wire.target="submitSupervisor">
                                <div class="circ1 loader-primary"></div>
                                <div class="circ2 loader-primary"></div>
                                <div class="circ3 loader-primary"></div>
                                <div class="circ4 loader-primary"></div>
                            </div>
                        </div>
                    @endif
                </form>

            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="sub-title">Supervisors</h5>
            </div>
            <div class="card-block">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <th>#</th>
                            <th>Supervisor</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Action</th>
                        </thead>
                        <tbody>
                            @php
                                $i = 1;
                            @endphp
                            @foreach ($supervisors as $super)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$super->user->first_name ?? ''}} {{$super->user->surname ?? ''}}</td>
                                    <td>{{$super->department->department_name ?? ''}}</td>
                                    <td>{{date('d F, Y', strtotime($super->created_at)) ?? ''}} @ <small>{{date('h:ia', strtotime($super->created_at))}}</small></td>
                                    <td>
                                        <a href="javascript:void(0);" wire:click="editSupervisor({{$super->user_id}})"> <i class="ti-pencil text-warning"></i> </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


