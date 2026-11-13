<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class PermissionsController extends Controller
{
    public function index(Request $request)
    {
        $data = Permissions::latest();

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at_formatted', function ($row) {
                    return Carbon::parse($row->created_at)->format('Y-m-d');
                })
                ->editColumn('updated_at_formatted', function ($row) {
                    return Carbon::parse($row->updated_at)->format('Y-m-d');
                })
                ->addColumn('action', function ($row) {
                    $btn = ' 
                    <div class="btn-grup"  style="white-space: nowrap !important;"> 
                    <!-- <button type="button" class="detilpermissions btn btn-success btn-sm"  id="'.$row->id.'"><i class="fas fa-th-list"></i></button> --> 
                    <button type="button" class="editpermissions btn btn-warning btn-sm"  id="'.$row->id.'" ><i class="fas fa-edit"></i></button>
                    <button type="button" class="deletepermissions btn btn-danger btn-sm text-white"  id="'.$row->id.'" ><i class="fas fa-trash-alt"></i></button> 
                    </div>
                     
                    ';

                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('permissions');
    }

    public function store(Request $request)
    {
        if ($request->operationpermissions == 'Add') {
            $permissions = new Permissions;
            $permissions->id = $request->id;
            $permissions->name = $request->name;
            $permissions->guard_name = 'web';
            $permissions->save();
            $lastid = $permissions->id;
        } else {
            $permissions = Permissions::where('id', $request->id)
                ->update([
                    'id' => $request->id,
                    'name' => $request->name,
                    'guard_name' => 'web',
                ]);
        }

        return response()->json([
            'pesan' => 'sukses',
        ]);
    }

    public function edit()
    {
        $id = $_GET['id'];
        $data = Permissions::findOrFail($id);
        $response = json_encode($data);

        return response()->json([
            'data' => $response,
            'pesan' => 'sukses',
        ]);
    }

    public function delete()
    {
        $id = $_GET['id'];
        $data = Permissions::where('id', $id)
            ->delete();

        return response()->json([
            'pesan' => 'sukses',
        ]);
    }

    public function permissionsoption($id = null)
    {
        $result = Permissions::get();
        $options = '';

        foreach ($result as $row) {
            $options .= '<option value="'.$row->id.'"';
            if ($id == $row->id) {
                $options .= ' selected';
            }
            $options .= '>'.$row->permissions.'</option>';
        }

        return $options;
    }
}
