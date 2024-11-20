<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserController extends Controller
{
    public function admin()
    {
        $breadcrumb = (object) [
            'title' => 'Pengguna',
            'list' => ['Home', 'Data Pengguna'],
        ];
        $activeMenu = 'user admin';
        return view('admin.user.index', ['breadcrumb' => $breadcrumb, 'activeMenu' => $activeMenu]);
    }

    public function pimpinan()
    {
        $breadcrumb = (object) [
            'title' => 'Pengguna',
            'list' => ['Home', 'Data Pengguna'],
        ];
        $activeMenu = 'user pimpinan';
        return view('pimpinan.user.index', ['breadcrumb' => $breadcrumb, 'activeMenu' => $activeMenu]);
    }

    public function levelAdmin()
    {
        $breadcrumb = (object) [
            'title' => 'Jenis Pengguna',
            'list' => ['Home', 'Data Jenis Pengguna'],
        ];
        $activeMenu = 'user jenis';
        return view('admin.jenispengguna.index', ['breadcrumb' => $breadcrumb, 'activeMenu' => $activeMenu]);
    }

    // function list (admin)
    public function list(Request $request)
    {
        $user = UserModel::select('id_user', 'username', 'nama', 'email', 'NIP', 'level');

        if ($request->level) {
            $user->where('level', $request->level);
        }

        return DataTables::of($user)
            ->addIndexColumn()
            ->addColumn('aksi', function ($user) {
                $btn = '<button onclick="modalAction(\'' . url('/admin/user/' . $user->id_user . '/show_ajax') . '\')" class="btn btn-info btn-sm">Detail</button> ';
                $btn .= '<button onclick="modalAction(\'' . url('/admin/user/' . $user->id_user . '/edit_ajax') . '\')" class="btn btn-warning btn-sm">Edit</button> ';
                $btn .= '<button onclick="modalAction(\'' . url('/admin/user/' . $user->id_user . '/delete_ajax') . '\')" class="btn btn-danger btn-sm">Hapus</button> ';
                return $btn;
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create_ajaxAdmin()
    {
        return view('admin.user.create_ajax');
    }

    public function store_ajaxAdmin(Request $request)
    {
        if($request->ajax() || $request->wantsJson()){
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:255|unique:t_user',
                'nama' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date',
                'email' => 'required|string|email|max:255|unique:t_user',
                'password' => 'required|string|min:5',
                'NIP' => 'required|string|max:255|unique:t_user',
                'level' => 'required|string|in:admin,user,pimpinan',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Validation failed',
                    'msgField' => $validator->errors()
                ]);
            }

            UserModel::create([
                'username' => $request->username,
                'nama' => $request->nama,
                'tanggal_lahir' => $request->tanggal_lahir,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'NIP' => $request->NIP,
                'level' => $request->level,
            ]);
            
            return response()->json([
                'status' => true, 
                'message' => 'User berhasil ditambahkan'
            ]);
        }
        return redirect('/admin/user');
    }

    // Function to show user details via AJAX (admin)
    public function show_ajaxAdmin(string $id)
    {
        $user = UserModel::find($id);

        if (!$user) {
            return response()->json(['error' => 'Data not found'], 404);
        }

        return view('admin.user.show_ajax', compact('user'));
    }

    // Function to edit user details via AJAX (admin)
    public function edit_AjaxAdmin(string $id)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return response()->json(['error' => 'Data not found'], 404);
        }

        return view('admin.user.edit_ajax', compact('user'));
    }

    public function update_ajaxAdmin(Request $request, string $id)
    {
        $user = UserModel::find($id);

        if (!$user) {
            return response()->json(['error' => 'Data not found'], 404);
        }

        if($request->ajax() || $request->wantsJson()){
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:255|unique:t_user,username,' . $id . ',id_user',
                'nama' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date',
                'email' => 'required|string|email|max:255|unique:t_user,email,' . $id . ',id_user',
                'NIP' => 'required|string|max:255|unique:t_user,NIP,' . $id . ',id_user',
                'level' => 'required|string|in:admin,dosen,pimpinan',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false, 
                    'message' => 'Validation failed',
                    'msgField' => $validator->errors()
                ]);
            }

            $user->update([
                'username' => $request->username,
                'nama' => $request->nama,
                'tanggal_lahir' => $request->tanggal_lahir,
                'email' => $request->email,
                'NIP' => $request->NIP,
                'level' => $request->level,
            ]);

            return response()->json([
                'status' => true, 
                'message' => 'User updated successfully'
            ]);
        }
        return redirect('/admin/user');
    }

    public function confirm_ajaxAdmin($id, Request $request)
    {
        if($request->ajax() || $request->wantsJson()){
            $user = UserModel::find($id);
            if (!$user) {
                return response()->json([
                'status' => false, 
                'message' => 'Data user tidak ditemukan']);
            }
        }
        return view('admin.user.confirm_ajax', compact('user'));
    }

    // Function to delete user via AJAX (admin)
    public function delete_ajaxAdmin(Request $request, $id)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $user = UserModel::find($id);
            if (!$user) {
                return response()->json(['error' => 'Data not found'], 404);
            }

            $user->delete();
        }
            return response()->json(['success' => 'User deleted successfully']);
    }
    // function export (admin)
    public function exportPdf()
    {
        $user = UserModel::select('id_user', 'username', 'nama', 'email', 'NIP', 'level')->get();
        $pdf = Pdf::loadView('admin.user.export_pdf', ['user' => $user]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption("isRemoteEnabled", true);
        $pdf->render();
        return $pdf->stream('Data Pengguna ' . date('Y-m-d H:i:s') . '.pdf');
    }

    public function exportExcel()
    {
        $users = UserModel::select('id_user', 'username', 'nama', 'email', 'NIP', 'level')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(); // Get the active sheet

        // Set Header Columns
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'ID User');
        $sheet->setCellValue('C1', 'Username');
        $sheet->setCellValue('D1', 'Nama Lengkap');
        $sheet->setCellValue('E1', 'Email');
        $sheet->setCellValue('F1', 'NIP');
        $sheet->setCellValue('G1', 'Level');

        // Make header bold
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $no = 1; // Data number starts from 1
        $row = 2; // Data row starts from row 2
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $user->id_user);
            $sheet->setCellValue('C' . $row, $user->username);
            $sheet->setCellValue('D' . $row, $user->nama);
            $sheet->setCellValue('E' . $row, $user->email);
            $sheet->setCellValue('F' . $row, $user->NIP);
            $sheet->setCellValue('G' . $row, $user->level);
            $row++;
            $no++;
        }

        // Set auto column width for all columns
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Set sheet title
        $sheet->setTitle('Data User');

        // Create writer
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'Data_User_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        // Save file to output
        $writer->save('php://output');
        exit;
    }
}