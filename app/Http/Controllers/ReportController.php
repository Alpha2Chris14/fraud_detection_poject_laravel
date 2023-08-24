<?php

namespace App\Http\Controllers;

use App\Mail\NotifyUser;
use App\Models\Account;
use App\Models\Report;
use App\Models\Threshold;
use App\Models\Proof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    //
    public function store(Request $request){
        // Acn no of the fraudster, Phone no of the fraudster(optional), Email of the reporter, Fraud Description, Proof (which would be an image)
        $acn = Account::where("account_no",$request->account_no)->first();
        $bank =Threshold::first();
        if($acn){
            $acn->threshold += 1; 
            if($acn->threshold >= $bank->threshold){
                $acn->status = 1;
            }
            $acn->save();
        }
        else{
            $acn = new Account();
            $acn->account_no = $request->account_no;
            $acn->threshold = 1;
            $acn->status = 0;
            $acn->save();
        }
        $report = new Report();
        $report->account_no = $request->account_no;
        $report->phone = $request->phone; //optional
        $report->email = $request->email;
        $report->description = $request->description;
        $report->bank_id = $request->bank;

        $uploadedFiles = [];
        $rand = $this->generateRandomAlphanumeric();
        $fileData = $request->file('proof');
        foreach ($fileData as $file) {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->store('uploads', 'public');
            $proof = new Proof();
            $proof->name = $fileName;
            $proof->rand = $rand;
            $proof->save();
            // $uploadedFiles[] = $fileName;
        }
        $report->proof = $rand;
        $report->save();

        $emailAddress = $request->email; // Use appropriate logic to retrieve the email address

        if (isset($emailAddress)) {
            // Send email
            Mail::to($emailAddress)->send(new NotifyUser());
        } else {
            return "failed";
        }

        return "success";
    }

    public function checkAccount(Request $request){
        $data = Account::where("account_no",$request->account_no)->first();
        $bank =Threshold::first();
        if(empty($data) || empty($bank)){
            return "success";
        }
        if(($data->threshold >= $bank->threshold) || $data->status == 1){
            $data->status = 1;
            $data->save();
            return "failed";
        }
        // return response(['threshold'=>$data->threshold,'data' => $data], 200);
        return "success";
    }
    public function setBankThreshold(Request $request){
        $bank =Threshold::where("name","LIKE","%".$request->name."%")->first();
        if(isset($bank)){
            $bank->name = $request->name;
            $bank->threshold = $request->threshold;
            $bank->save();
            return "success";
        }
        $bank = new Threshold();
        $bank->name = $request->name;
        $bank->threshold = $request->threshold;
        $bank->save();
        return "success";
    }
    public function downloadExcel()
    {
        $acns = Account::all();
        $data = [
            ['account number','time','class'],
        ];

        foreach($acns as $acn){
            array_push($data,[$acn->account_no,$acn->created_at,$acn->status]);
        }
        
        $fileName = 'dataset.csv';
        
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
        
        $handle = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        return response()->stream(
            function () use ($handle) {
                fclose($handle);
            },
            200,
            $headers
        );
        
    }

    public function generateRandomAlphanumeric($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
    
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
    
        return $randomString;
    }

    public function banks(){
        $banks = Threshold::all();
        return response()->json($banks);
    }

    public function getReportsForBank(Request $request){
        // return var_dump($request->bank);
        $data = Report::where("bank_id",$request->bank)->get();
        return response()->json($data);
    }
    public function approve(Request $request){
        //get the report by acn and bank_code/id
        $report = Report::find($request->id);
        if($report->status == 1){
            return "failed";
        }
        $report->status = 1;
        $report->save();
        
        $data = Account::where("account_no",$report->account_no)
        ->first();
        //retrieve the bank information
        $bank =Threshold::find($request->bank);
        if(($data->threshold >= $bank->threshold) || $data->status != 1){
            $data->status = 1;
            $data->save();
        }
        // return response(['threshold'=>$data->threshold,'data' => $data], 200);
        return "success";
    }
}
