<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Filament\Notifications\Notification;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Illuminate\Support\Facades\Auth;

class DirectPrintService
{
    public function print($orderToPrint)
    {
        try {
            // 1. Ambil Data Transaksi
            $order = Transaction::with('paymentMethod')->findOrFail($orderToPrint);
            $order_items = TransactionItem::where('transaction_id', $order->id)->get();
            
            // 2. Ambil Data Setting
            $setting = Setting::first();
            $namaToko = $setting->name ?? 'TOKO SAYA'; 
            $alamatToko = $setting->address ?? '-';
            $teleponToko = $setting->phone ?? '-';

            // --- LOGIKA PEMILIHAN PRINTER ---
            // Jika Bluetooth Aktif di Setting, pakai 'name_printer_bluetooth'
            // Jika tidak, pakai 'name_printer_local'
            if (Setting::isBluetoothEnabled()) {
    // Pastikan kolom ini ada di database, jika namanya berbeda, sesuaikan!
    $namaPrinter = $setting->print_via_bluetooth; 
} else {
    // Sesuai gambar database kamu: name_printer_local
    $namaPrinter = $setting->name_printer_local; 
}

if (empty($namaPrinter)) {
    throw new \Exception("Nama printer (Bluetooth/Local) kosong di database.");
}

            // 3. Koneksi Printer
            $connector = new WindowsPrintConnector($namaPrinter);
            $printer = new Printer($connector);

            $lineWidth = 32; // Standar kertas 58mm

            // --- PROSES CETAK (HEADER) ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);

            // Cetak Logo jika ada
            try {
                if ($setting && $setting->image) {
                    $logoPath = public_path('storage/' . $setting->image);
                    if (file_exists($logoPath)) {
                        $logo = EscposImage::load($logoPath, false);
                        $printer->bitImage($logo);
                    }
                }
            } catch (\Exception $e) { }

            // Info Toko
            $printer->setTextSize(2, 2); 
            $printer->setEmphasis(true);
            $printer->text(strtoupper($namaToko) . "\n"); 
            
            $printer->setTextSize(1, 1);
            $printer->setEmphasis(false);
            $printer->text($alamatToko . "\n");
            $printer->text("Telp: " . $teleponToko . "\n");
            $printer->text("================================\n");

            // --- DETAIL TRANSAKSI ---
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text('No. Transaksi : ' . $order->transaction_number . "\n");
            
            $kasir = Auth::check() ? Auth::user()->name : 'Admin';
            $printer->text('Kasir         : ' . $kasir . "\n");
            
            $printer->text('Tanggal       : ' . $order->created_at->format('d-m-Y H:i') . "\n");
            $printer->text('Metode Bayar  : ' . ($order->paymentMethod->name ?? 'Tunai') . "\n");
            $printer->text("--------------------------------\n");
            
            $printer->text($this->formatRow('Item', 'Qty', 'Harga', $lineWidth) . "\n");
            $printer->text("--------------------------------\n");

            // --- LIST BARANG ---
            foreach ($order_items as $item) {
                $product = Product::find($item->product_id);
                $namaProduk = $product->name ?? 'Produk';
                
                // Format: Nama Produk (di baris sendiri jika panjang), lalu Qty & Harga
                $printer->text($this->formatRow($namaProduk, $item->quantity, number_format($item->price), $lineWidth) . "\n");
            }

            $printer->text("--------------------------------\n");

            // --- FOOTER (TOTALAN) ---
            $printer->setEmphasis(true);
            $printer->text($this->formatRow('TOTAL', '', number_format($order->total), $lineWidth) . "\n");
            $printer->setEmphasis(false);
            $printer->text($this->formatRow('TUNAI', '', number_format($order->cash_received), $lineWidth) . "\n");
            $printer->text($this->formatRow('KEMBALI', '', number_format($order->change), $lineWidth) . "\n");
            
            $printer->text("================================\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Terima Kasih\n");
            $printer->text("Atas Kunjungan Anda\n");
            $printer->text("================================\n");

            // Selesai
            $printer->cut();
            $printer->close();

            Notification::make()->title('Struk berhasil dicetak')->success()->send();

        } catch (\Exception $e) {
            if (isset($printer)) {
                try { $printer->close(); } catch (\Exception $ex) {}
            }

            Notification::make()
                ->title('Gagal Mencetak')
                ->body("Cek koneksi printer: " . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function formatRow($name, $qty, $price, $lineWidth)
    {
        $nameWidth = 16; 
        $qtyWidth = 5;   
        $priceWidth = 11; 

        $nameLines = str_split($name, $nameWidth);
        $output = '';

        // Jika nama produk panjang, cetak potongannya dulu
        for ($i = 0; $i < count($nameLines) - 1; $i++) {
            $output .= str_pad($nameLines[$i], $lineWidth) . "\n";
        }

        // Baris terakhir nama produk digabung dengan Qty dan Harga
        $lastLine = str_pad($nameLines[count($nameLines) - 1], $nameWidth);
        $qty = str_pad($qty, $qtyWidth, ' ', STR_PAD_BOTH);
        $price = str_pad($price, $priceWidth, ' ', STR_PAD_LEFT);

        return $output . $lastLine . $qty . $price;
    }
}