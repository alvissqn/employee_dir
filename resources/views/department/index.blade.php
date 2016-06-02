@extends('layouts.app')

@section('content')
    <div class="container">
        <h5>Departments</h5>
        @if(!Auth::guest())
            <div class="fixed-action-btn" style="bottom: 45px; right: 24px;">
                <a href="{{url('/department/add')}}" class="btn-floating btn-large waves-effect waves-light green" title="Add department">
                    <i class="large material-icons">add</i>
                </a>
            </div>
        @endif
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Office Number</th>
                    <th>Manager</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($departments as $index=>$dp)
                <tr id="{{'info-'.$dp->id}}">
                    <td>{{($departments->currentPage()-1)*8+$index+1}}</td>
                    <td><a href="{{url('/department').'/'.$dp->id.'/detail'}}" id="dpName">{{$dp->name}}</a></td>
                    <td id="dpOfficeNumber">{{$dp->office_number}}</td>
                    <td>
                        @if($dp->manager())
                            <a href="{{url('/employee').'/'.$dp->manager()->id.'/detail'}}"id="dpManager">{{$dp->manager()->name}}</a>
                        @endif
                    </td>
                    <td>
                        <a href="{{url('/department').'/'.$dp->id.'/employee'}}" class="btn-floating blue" title="Employee list"><i class="material-icons">view_list</i></a>
                        @if(!Auth::guest())
                            <a class="btn-floating green" title="Edit" id="{{'show-edit-'.$dp->id}}"><i class="material-icons">mode_edit</i></a>
                            <div id="confirmDelete" class="modal">
                                <div class="modal-content">
                                    <p>Are you sure want to delete this?</p>
                                </div>
                                <div class="modal-footer">
                                    <button href="#" class="modal-action modal-close waves-effect waves-light btn-flat">Cancel</button>
                                    <button id="confirm" class="modal-action modal-close waves-effect waves-light btn-flat red">Delete</button>
                                </div>
                            </div>
                            <form role="form" method="POST" action="{{url('/department').'/'.$dp->id.'/delete'}}" accept-charset="UTF-8" style="display:inline" id="deleteForm">
                                {{csrf_field()}}
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" class="form-control" name="dp-id" value="{{$dp->id}}">
                                <button class="btn-floating red modal-trigger" type="button" href="#confirmDelete" title="Delete">
                                    <i class="material-icons">delete</i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                <tr id="{{'edit-'.$dp->id}}" hidden class="tr-edit">
                    <td>{{($departments->currentPage()-1)*8+$index+1}}</td>
                    <td>
                        <input type="text" class="form-control input-sm" value="{{$dp->name}}">
                    </td>
                    <td>
                        <input type="text" class="form-control input-sm" value="{{$dp->office_number}}">
                    </td>
                    <td>
                        <select>
                            @if(sizeof($employees))
                                @foreach($employees as $em)
                                    @if($em->id==$dp->manager_id)
                                        <option value="{{$em->id}}" selected>{{$em->name}}</option>
                                    @else
                                        <option value="{{$em->id}}">{{$em->name}}</option>
                                    @endif
                                @endforeach
                            @else
                                <option selected></option>
                            @endif
                        </select>
                    </td>
                    <td>
                        @if(!Auth::guest())
                            <a class="waves-effect waves-light btn white" id="{{'save-'.$dp->id}}">
                                <i class="material-icons" style="color: green;">done</i>
                            </a>
                            <a class="waves-effect waves-light btn white" id="{{'cancel-'.$dp->id}}">
                                <i class="material-icons" style="color: red;">cancel</i>
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
        <div class="divider"></div>
        <center>
            {!! $departments->render() !!}
        </center>
    </div>

    <!-- Edit result modal -->
    <div class="modal fade" id="editResult" role="dialog">
        <div class="modal-dialog" style="margin-top: 5%">
            <div class="col-sm-8 col-sm-offset-2" id="editAlert" style="text-align: center">
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $('.modal-trigger').leanModal();
        $('select').material_select();
        <!-- Form confirm (yes/ok) handler, submits form -->
        $('#confirmDelete').find('.modal-footer #confirm').on('click', function(){
            $('#deleteForm').submit();
        });
        
        {{--Show/hide edit form--}}
        
        $('[id^="show-edit-"]').on('click', function() {
            id = $(this).attr('id').substr(10);
            $('#info-' + id).hide();
            $('#edit-' + id).show();    
        });
        
        $('[id^="cancel-"]').on('click', function() {
            id = $(this).attr('id').substr(7);
            $('#edit-' + id).hide();
            $('#info-' + id).show();
        });
        
        $('[id^="save-"]').on('click', function() {
            id = $(this).attr('id').substr(5);
            edit_data = $('#edit-' + id).children();
            $.ajax({
                type: 'POST',
                url:  '/department/' + id + '/edit',
                data: {
                    'dp-id': $('input[name="dp-id"]').val(),
                    'dp-name': edit_data.eq(1).children().eq(0).val(),
                    'dp-office-number': edit_data.eq(2).children().eq(0).val(),
                    'dp-manager-id': edit_data.eq(3).children().eq(0).val(),
                    '_token': $('input[name=_token]').val()
                },
                success: function(data){
                    data = JSON.parse(data);
                    if (data.alert_type == 'success') {
                        $('#dpName').html(data.dp.name);
                        $('#dpOfficeNumber').html(data.dp.office_number);
                        $('#dpManager').html(data.dp.manager_name);
                        
                        $('#edit-' + id).hide();
                        $('#info-' + id).show();
                    }
                    $('#editAlert').html('<div class="alert alert-' + data.alert_type +' alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="modal">&times;</button><strong>' + data.result + '</strong></div>');
                    $('#editResult').modal();
                },
                error: function(data) {
                    var errors = data.responseJSON;
                    error = errors[Object.keys(errors)[0]][0];
                    $('#editAlert').html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="modal">&times;</button><strong>' + error + '</strong></div>');
                    $('#editResult').modal();
                }
            });
        });
    </script>
@endsection