<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1-0">
    <title>Order Anda</title>
    <style>
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 5px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }

        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }

        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }

            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }

        .trait{
            width: 50%;
            /* display: inline-block; */
            float: left;
        }

        /** RTL **/
        .invoice-box.rtl {
            direction: rtl;
            font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        }

        .invoice-box.rtl table {
            text-align: right;
        }

        .invoice-box.rtl table tr td:nth-child(2) {
            text-align: left;
        }
    </style>
</head>

<body>
    @php
    $data_detail = json_decode($bookings->data_detail);
    $buyer_fees = json_decode($bookings->buyer_fees);
    @endphp
    <div id="content">
        <div class="content-wrap page-my-rides">
            <div class="subsite">
                <div class="invoice-box">
                    <table cellpadding="0" cellspacing="0">
                        <tr class="top">
                            <td colspan="2">
                                <table>
                                    <tr>
                                        <td class="title">
                                            <img src="https://m.pulo1000.com/assets/img/pulologo.png"
                                                style="width: 100%; max-width: 180px" />
                                        </td>

                                        <td style="text-align: right!important;">
                                            Invoice:
                                            PBDIGI-{{App\Models\Payment::where('code',$bookings->code)->first()->bill_no}}<br />
                                            Kode Transaksi:
                                            {{App\Models\Payment::where('code',$bookings->code)->first()->txr_id}}<br>
                                            Dibuat: {{$bookings->created_at}}<br />
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr class="information">
                            <td colspan="2">
                                <table>
                                    <tr class="trait">
                                        <td>
                                            {{$bookings->first_name}} {{$bookings->last_name}}.<br />
                                            {{$bookings->address}}
                                        </td>
                                    </tr>

                                    <tr class="trait">
                                        <td style="font-size: 15px;">
                                            Nama: PT. PBDIGITAL TECHNOLOGY INDONESIA<br />
                                            Alamat: Yes Building 6th Floor, Jl. Aipda KS Tubun No. 85, Slipi, Palmerah,
                                            Jakarta Barat 11410<br />
                                            NPWP: 41.823.574.3-031.000
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr class="heading">
                            <td>Metode Pembayaran</td>
                            <td>#Status</td>
                        </tr>

                        <tr class="details">

                            <td>{{App\Models\Payment::where('code',$bookings->code)->first()->channel_name}}</td>

                            @if(App\Models\Payment::where('code',$bookings->code)->first()->status == 'waiting')
                            <td>Menunggu pembayaran</td>
                            @else
                            <td>{{App\Models\Payment::where('code',$bookings->code)->first()->status}}</td>
                            @endif

                        </tr>


                        <tr class="heading">
                            <td>Item</td>

                            <td>Harga</td>
                        </tr>
                        <tr class="total last">
                            <td>
								{{$data_detail->title}}
								<br><br>
								{{$data_detail->port_from_name}} - {{$data_detail->port_to_name}}
								<br>
								Berangkat : {{$data_detail->berangkat.' '.$data_detail->departure_time}}
							</td>
                            @php $price_fee = $bookings->total_before_fees; @endphp
                            <td style="vertical-align: middle;">Rp {{number_format($price_fee,0,',','.')}}</td>
                        </tr>

                        <tr style="border: 2px solid #eee;" class="total last">
                            <td>Biaya Penanganan</td>
                            <td>Rp
                                {{number_format(intval($buyer_fees->admin_fee->dewasa+$buyer_fees->admin_fee->anak),0,',','.')}}
                            </td>
                        </tr>
                        <tr style="border: 2px solid #eee;" class="total last">
                            <td>Biaya Transfer</td>
                            <td>Rp 4.440</td>
                        </tr>
                        <tr style="border: 2px solid #eee;" class="total last">
                            <td>Total: </td>
                            <td>Rp {{strpos($bookings->total,',') ? substr(str_replace(',','.',$bookings->total),0,-3) :
                                number_format(intval($bookings->total),0,',','.') }}</td>
                        </tr>
                    </table>
					<br>
					<table>
							<tr class="heading" style="border: 2px solid #eee;text-align:center;">
								<td style="text-align:center;border:2px solid #eee;text-align:center;">Nama</td>
								<td style="text-align:center;border:2px solid #eee;">No KTP/Paspor/KK</td>
								<td style="text-align:center;border:2px solid #eee;text-align:center;">Umur</td>
								<td style="text-align:center;border:2px solid #eee;text-align:center;">Kelamin</td>
							</tr>
							@for($i=0;$i<sizeof($data_detail->data_penumpang->dewasa);$i++)
							<tr class="details" style="border: 2px solid #eee;text-align:center;">
								<td style="vertical-align:middle;border:2px solid #eee;text-align:center;">{{$data_detail->data_penumpang->dewasa[$i]->nama}}</td>
								<td style="vertical-align:middle;border:2px solid #eee;text-align:center;">{{$data_detail->data_penumpang->dewasa[$i]->no_identitas}}</td>
								<td style="vertical-align:middle;border:2px solid #eee;text-align:center;">{{$data_detail->data_penumpang->dewasa[$i]->umur}}</td>
								<td style="vertical-align:middle;border:2px solid #eee;text-align:center;">{{$data_detail->data_penumpang->dewasa[$i]->jenis_kelamin}}</td>
							</tr>
							@endfor
							@for($i=0;$i<sizeof($data_detail->data_penumpang->anak);$i++)
							<tr class="details" style="border: 2px solid #eee;text-align:center;">
								<td style="vertical-align:middle;border:2px solid #eee;text-align:center;">{{$data_detail->data_penumpang->anak[$i]->nama}}</td>
								<td style="vertical-align:middle;border:2px solid #eee;text-align:center;">{{$data_detail->data_penumpang->dewasa[$i]->no_identitas}}</td>
								<td style="vertical-align:middle;border:2px solid #eee;text-align:center;">{{$data_detail->data_penumpang->anak[$i]->umur}}</td>
								<td style="vertical-align:middle;border:2px solid #eee;text-align:center;">{{$data_detail->data_penumpang->anak[$i]->jenis_kelamin}}</td>
							</tr>
							@endfor
					</table>
                    <p style="    margin-top: 20px;font-size: 10px;font-style: italic;">*Invoice ini berlaku sebagai
                        faktur pajak.</p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>