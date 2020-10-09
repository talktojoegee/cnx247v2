<div>
    <div class="row">
        <div class="col-md-12 col-xl-12">
            <div class="card">
                <div class="card-block">
                    @include('livewire.backend.crm.common._slab-menu')
                </div>
            </div>
        </div>
   </div>
   <div class="row">
    <div class="col-md-12 col-xl-12 filter-bar">
        <nav class="navbar navbar-light bg-faded m-b-30 p-10">
            <div class="input-group col-md-8 offset-md-1 mt-2">
                <input type="text" class="form-control" placeholder="Search for client (Ex. Joseph Name)" wire:model.debounce.90000ms="client_name">
                <span class="input-group-addon btn-mini " wire:click="searchForClient" id="basic-addon5"><i class="ti-search mr-2"></i> Search client</span>
            </div>
            @error('client_name')
                <i class="text-danger">{{$message}}</i>
            @enderror
        </nav>
    </div>
</div>
   <div class="row">
    <div class="col-lg-12 col-xl-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-header-text">Clients</h5>
                <div class="btn-group float-right">
                    <a href="{{route('new-client')}}" class="btn btn-primary btn-mini"> <i class="ti-plus mr-2"></i> Add New Client</a>
                </div>
            </div>
            <div class="card-block p-b-0">
                <div class="row">
                    <div class="col-md-12" id="draggableMultiple">
                        <div class="row">
                            @if (count($clients) > 0)
                                @foreach ($clients as $client)
                                    <div class="col-md-6">
                                        <div class="sortable-moves" style="cursor: auto;">
                                            <img class="img-fluid p-absolute" src="/assets/images/avatars/thumbnails/avatar.png" alt="">
                                                <span id="clientAvatarBtn" style="cursor: pointer"><i class="ti-camera"></i></span>
                                                <input type="file" hidden id="clientAvatar">
                                                <table class="table m-0">
                                                    <tbody>
                                                        <tr>
                                                            <th scope="row">Full Name</th>
                                                            <td>{{$client->title ?? ''}} {{$client->first_name ?? ''}} {{$client->surname ?? ''}}</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Mobile</th>
                                                            <td>{{$client->mobile_no ?? ''}}</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Email</th>
                                                            <td>{{$client->email ?? ''}}</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Website</th>
                                                            <td>{{$client->website ?? ''}}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            <div class="row">
                                                <div class="col-md-12 d-flex justify-content-end">
                                                    <div class="btn-group mr-3">
                                                        <a href="{{route('edit-client', $client->slug)}}"><i class="ti-pencil text-warning p-2"></i></a>
                                                        <a href="{{route('view-client', $client->slug)}}"><i class="ti-eye text-primary p-2"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-md-12">
                                    <h4 class="text-center">No record found.</h4>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@push('client-script')
<script>
    $(document).ready(function(){
        $(document).on('click', '#clientAvatarBtn', function(e){
            e.preventDefault();
            $('#clientAvatar').click();
            $('#clientAvatar').on('change',function(event){
                alert('changed');
            });
        });
    });
</script>
@endpush
