<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\Checksheet;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpWord\IOFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class WordController extends Controller
{
    public function __invoke(Request $request, $order)
    {
        // Fetch the Checksheet record based on the provided order (id)
        $checksheet = Checksheet::findOrFail($order); // Get Checksheet by id

        $templateFile = '';

        switch ($checksheet->tipe_proses) {
            case 'Drawing':
                $templateFile = 'CheckSheetDrawing.docx';
                break;
            case 'Stranding':
                $templateFile = 'CheckSheetStranding.docx';
                break;
            case 'Cabling':
                $templateFile = 'CheckSheetCabling.docx';
                break;
            case 'Extruder':
                $templateFile = 'CheckSheetExtruder.docx';
                break;
            case 'Bunching':
                $templateFile = 'CheckSheetBunching.docx';
                break;
            case 'Tapping':
                $templateFile = 'CheckSheetTapping.docx';
                break;
            case 'Tinning':
                $templateFile = 'CheckSheetTinning.docx';
                break;
            case 'Coloring':
                $templateFile = 'CheckSheetColoring.docx';
                break;
            default:
                // Set a default template or handle the case when 'tipe_proses' doesn't match any of the expected values
                // $templateFile = 'CheckSheetDefault.docx';
                break;
        }

        // Initialize the template processor with the Word template file
        $templateProcessor = new TemplateProcessor($templateFile);

        // ELEKTRIK
        $templateProcessor->setValue('tipe_proses', $checksheet->tipe_proses);
        $templateProcessor->setValue('plant_area', $checksheet->plant_area);
        $templateProcessor->setValue('nama_mesin', $checksheet->nama_mesin);
        $templateProcessor->setValue('hours_meter', $checksheet->hours_meter);
        $templateProcessor->setValue('date', Carbon::parse($checksheet->date)->format('d-M-Y'));
        $templateProcessor->setValue('time_start', $checksheet->time_start);
        $templateProcessor->setValue('time_end', $checksheet->time_end);
        $templateProcessor->setValue('nama_operator', $checksheet->nama_operator);
        //elektrik for drawing
        $templateProcessor->setValue('elektrik_carbon_brush', $checksheet->elektrik_carbon_brush);
        $templateProcessor->setValue('elektrik_suara', $checksheet->elektrik_suara);
        $templateProcessor->setValue('elektrik_getaran', $checksheet->elektrik_getaran);
        $templateProcessor->setValue('elektrik_suhu', $checksheet->elektrik_suhu);
        $templateProcessor->setValue('elektrik_tahanan_isolasi', $checksheet->elektrik_tahanan_isolasi);
        $templateProcessor->setValue('elektrik_ampere_motor', $checksheet->elektrik_ampere_motor);
        $templateProcessor->setValue('remarks_elektrik_motor_penggerak', $checksheet->remarks_elektrik_motor_penggerak);

        $templateProcessor->setValue('elektrik_tombol', $checksheet->elektrik_tombol);
        $templateProcessor->setValue('elektrik_layar', $checksheet->elektrik_layar);
        $templateProcessor->setValue('elektrik_PLC', $checksheet->elektrik_PLC);
        $templateProcessor->setValue('elektrik_kontaktor', $checksheet->elektrik_kontaktor);
        $templateProcessor->setValue('elektrik_drive-inverter', $checksheet->{'elektrik_drive-inverter'});
        $templateProcessor->setValue('remarks_elektrik_sistem_control', $checksheet->remarks_elektrik_sistem_control);

        $templateProcessor->setValue('elektrik_kondisi_kabel', $checksheet->elektrik_kondisi_kabel);
        $templateProcessor->setValue('elektrik_socket_kabel', $checksheet->elektrik_socket_kabel);
        $templateProcessor->setValue('remarks_elektrik_kabel_dan_konektor', $checksheet->remarks_elektrik_kabel_dan_konektor);

        $templateProcessor->setValue('elektrik_kebocoran', $checksheet->elektrik_kebocoran);
        $templateProcessor->setValue('elektrik_tekanan', $checksheet->elektrik_tekanan);
        $templateProcessor->setValue('remarks_elektrik_sistem_hidrolik', $checksheet->remarks_elektrik_sistem_hidrolik);

        $templateProcessor->setValue('elektrik_filter', $checksheet->elektrik_filter);
        $templateProcessor->setValue('elektrik_blower', $checksheet->elektrik_blower);
        $templateProcessor->setValue('elektrik_sirkulasi', $checksheet->elektrik_sirkulasi);
        $templateProcessor->setValue('remarks_elektrik_sistem_pendingin_motor', $checksheet->remarks_elektrik_sistem_pendingin_motor);

        if ($checksheet->tipe_proses == 'Coloring') {
            $templateProcessor->setValue('elektrik_kebersihan', $checksheet->elektrik_kebersihan);
            $templateProcessor->setValue('elektrik_aliran_cairan', $checksheet->elektrik_aliran_cairan);
            $templateProcessor->setValue('elektrik_tekanan_coloring', $checksheet->elektrik_tekanan_coloring);
            $templateProcessor->setValue('remarks_elektrik_sistem_pewarna', $checksheet->remarks_elektrik_sistem_pewarna);

            $templateProcessor->setValue('elektrik_sispemanas_suhu', $checksheet->elektrik_sispemanas_suhu);
            $templateProcessor->setValue('elektrik_kestabilan_pemanas', $checksheet->elektrik_kestabilan_pemanas);
            $templateProcessor->setValue('remarks_elektrik_sistem_pemanas', $checksheet->remarks_elektrik_sistem_pemanas);
        }

        // tipe_proses == Extruder
        if ($checksheet->tipe_proses == 'Extruder') {
            $templateProcessor->setValue('elektrik_suhu_heater', $checksheet->elektrik_suhu_heater);
            $templateProcessor->setValue('elektrik_fungsi_pemanas', $checksheet->elektrik_fungsi_pemanas);
            $templateProcessor->setValue('remarks_elektrik_heater', $checksheet->remarks_elektrik_heater);

            $templateProcessor->setValue('elektrik_kalibrasi_suhu', $checksheet->elektrik_kalibrasi_suhu);
            $templateProcessor->setValue('remarks_elektrik_thermocouple', $checksheet->remarks_elektrik_thermocouple);
        }

        if ($checksheet->tipe_proses == 'Tinning') {
            $templateProcessor->setValue('elektrik_sispemanas_suhu', $checksheet->elektrik_sispemanas_suhu);
            $templateProcessor->setValue('elektrik_kestabilan_pemanas', $checksheet->elektrik_kestabilan_pemanas);
            $templateProcessor->setValue('remarks_elektrik_sistem_pemanas', $checksheet->remarks_elektrik_sistem_pemanas);

            $templateProcessor->setValue('elektrik_kebersihan_kipas', $checksheet->elektrik_kebersihan_kipas);
            $templateProcessor->setValue('elektrik_fungsi_kipas', $checksheet->elektrik_fungsi_kipas);
            $templateProcessor->setValue('remarks_elektrik_sistem_pendingin_tinning', $checksheet->remarks_elektrik_sistem_pendingin_tinning);
        }
        //MEKANIK

        $templateProcessor->setValue('mekanik_gearbox_pelumasan', $checksheet->mekanik_gearbox_pelumasan);
        $templateProcessor->setValue('mekanik_gearbox_kebersihan', $checksheet->mekanik_gearbox_kebersihan);
        $templateProcessor->setValue('mekanik_gearbox_suara', $checksheet->mekanik_gearbox_suara);
        $templateProcessor->setValue('remarks_mekanik_gearbox', $checksheet->remarks_mekanik_gearbox);

        $templateProcessor->setValue('mekanik_shaft_keausan', $checksheet->mekanik_shaft_keausan);
        $templateProcessor->setValue('mekanik_shaft_kerusakan', $checksheet->mekanik_shaft_kerusakan);
        $templateProcessor->setValue('remarks_mekanik_shaft', $checksheet->remarks_mekanik_shaft);

        if (in_array($checksheet->tipe_proses, ['Drawing', 'Cabling', 'Bunching', 'Tapping', 'Stranding', 'Tinning'])) {
            $templateProcessor->setValue('mekanik_rollcap_keausan', $checksheet->mekanik_rollcap_keausan);
            $templateProcessor->setValue('mekanik_rollcap_kerusakan', $checksheet->mekanik_rollcap_kerusakan);
            $templateProcessor->setValue('remarks_mekanik_rollcap', $checksheet->remarks_mekanik_rollcap);
        }

        if ($checksheet->tipe_proses == 'Stranding') {
            $templateProcessor->setValue('mekanik_gearrantai_pelumasan', $checksheet->mekanik_gearrantai_pelumasan);
            $templateProcessor->setValue('mekanik_gearrantai_keausan', $checksheet->mekanik_gearrantai_keausan);
            $templateProcessor->setValue('remarks_mekanik_gearrantai', $checksheet->remarks_mekanik_gearrantai);
        }

        if ($checksheet->tipe_proses == 'Drawing') {
            $templateProcessor->setValue('mekanik_anealing_anealing', $checksheet->mekanik_anealing_anealing);
            $templateProcessor->setValue('mekanik_anealing_carbon_brush', $checksheet->mekanik_anealing_carbon_brush);
            $templateProcessor->setValue('remarks_mekanik_anealing', $checksheet->remarks_mekanik_anealing);
        }

        if ($checksheet->tipe_proses == 'Extruder') {
            $templateProcessor->setValue('mekanik_screewbarel_kondisi', $checksheet->mekanik_screewbarel_kondisi);
            $templateProcessor->setValue('mekanik_screewbarel_kerusakan', $checksheet->mekanik_screewbarel_kerusakan);
            $templateProcessor->setValue('remarks_mekanik_screewbarel', $checksheet->remarks_mekanik_screewbarel);
        }

        if ($checksheet->tipe_proses == 'Tinning') {
            $templateProcessor->setValue('mekanik_mesintinning_kebersihan', $checksheet->mekanik_mesintinning_kebersihan);
            $templateProcessor->setValue('mekanik_mesintinning_roller', $checksheet->mekanik_mesintinning_roller);
            $templateProcessor->setValue('remarks_mekanik_mesintinning', $checksheet->remarks_mekanik_mesintinning);
        }

        if ($checksheet->tipe_proses == 'Coloring') {
            $templateProcessor->setValue('mekanik_sispencoloring_aliran', $checksheet->mekanik_sispencoloring_aliran);
            $templateProcessor->setValue('mekanik_sispencoloring_kebersihan_pipa', $checksheet->mekanik_sispencoloring_kebersihan_pipa);
            $templateProcessor->setValue('mekanik_sispencoloring_flowmeter_n2', $checksheet->mekanik_sispencoloring_flowmeter_n2);
            $templateProcessor->setValue('remarks_mekanik_sispencoloring', $checksheet->remarks_mekanik_sispencoloring);
        }

        $templateProcessor->setValue('mekanik_sispendingin_aliran_pendingin', $checksheet->mekanik_sispendingin_aliran_pendingin);
        $templateProcessor->setValue('mekanik_sispendingin_kebersihan_pipa', $checksheet->mekanik_sispendingin_kebersihan_pipa);
        $templateProcessor->setValue('mekanik_sispendingin_sirkulasi_pipa', $checksheet->mekanik_sispendingin_sirkulasi_pipa);
        $templateProcessor->setValue('remarks_mekanik_sispendingin', $checksheet->remarks_mekanik_sispendingin);

        $templateProcessor->setValue('mekanik_pulleybelt_kekencangan', $checksheet->mekanik_pulleybelt_kekencangan);
        $templateProcessor->setValue('mekanik_pulleybelt_ketebalan', $checksheet->mekanik_pulleybelt_ketebalan);
        $templateProcessor->setValue('remarks_mekanik_pulleybelt', $checksheet->remarks_mekanik_pulleybelt);

        $templateProcessor->setValue('mekanik_bearing_pelumasan', $checksheet->mekanik_bearing_pelumasan);
        $templateProcessor->setValue('mekanik_bearing_kondisi_fisik', $checksheet->mekanik_bearing_kondisi_fisik);
        $templateProcessor->setValue('remarks_mekanik_bearing', $checksheet->remarks_mekanik_bearing);

        $templateProcessor->setValue('mekanik_alignmesin_kesejajaran', $checksheet->mekanik_alignmesin_kesejajaran);
        $templateProcessor->setValue('remarks_mekanik_alignmesin', $checksheet->remarks_mekanik_alignmesin);

        // Check if the base64 signature exists in the database
        // if ($checksheet->signature) {
        //     // Step 1: Extract the base64 image data (remove the "data:image/png;base64," part)
        //     $imageData = $checksheet->signature;

        //     // Remove the base64 prefix (e.g., "data:image/png;base64,")
        //     $imageData = str_replace('data:image/png;base64,', '', $imageData);

        //     // Step 2: Decode the base64 data into binary image data
        //     $imageDataDecoded = base64_decode($imageData);

        //     // Step 3: Save the decoded image data to a temporary PNG file
        //     $tempImageFile = tempnam(sys_get_temp_dir(), 'signature_') . '.png';
        //     file_put_contents($tempImageFile, $imageDataDecoded);

        //     // Step 4: Insert the image into the template at a placeholder
        //     // Ensure you have an image placeholder in your template like {signature_placeholder}
        //     $templateProcessor->setImageValue('signature', [
        //         'path' => $tempImageFile,
        //         'width' => 202.4,  // Adjust width as necessary
        //         'height' => 2476.8, // Adjust height as necessary
        //         'align' => 'center',
        //     ]);

        //     // Step 5: Clean up the temporary image file after it's added to the document
        //     unlink($tempImageFile);
        // }

        $templateProcessor->setImageValue('user', 'user.jpg');
        $templateProcessor->setImageValue('sukino', 'Sukino.jpg');
        $templateProcessor->setImageValue('suparta', 'Suparta.jpg');
        $templateProcessor->setImageValue('sofyan', 'Sofyan.jpg');

        // Generate the file name dynamically based on Checksheet ID or any other attribute
        $fileName = 'Checksheet_' . $checksheet->tipe_proses . ' ' . Carbon::parse($checksheet->date)->format('d-M-Y') . '.docx';

        // Step 6: Save the processed file to a temporary location
        $tempFilePath = storage_path('app/public/' . $fileName);
        $templateProcessor->saveAs($tempFilePath);

        // Return the generated Word file as a downloadable response
        return Response::download($tempFilePath)->deleteFileAfterSend(true);
    }
}
