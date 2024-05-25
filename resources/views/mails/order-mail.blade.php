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
    		border-left: 2px solid #eee;
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
					display: table-cell;
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
												<img src="https://m.pulo1000.com/assets/img/pulologo.png" style="margin: auto;width: 100%; max-width: 180px" />
											</td>

											<td style="text-align: right!important;">
												Invoice: PBDIGI-{{App\Models\Payment::where('code',$order->code_booking)->first()->bill_no}}<br />
												Kode Transaksi: {{App\Models\Payment::where('code',$order->code_booking)->first()->trx_id}}<br/>
												@if(App\Models\Payment::where('code',$order->code_booking)->first()->payment_date)
												Lunas:{{App\Models\Payment::where('code',$order->code_booking)->first()->payment_date}}
												@else
												Dibuat: {{App\Models\Payment::where('code',$order->code_booking)->first()->bill_date}}<br />
												@endif
											</td>
										</tr>
									</table>
								</td>
							</tr>

							<tr class="information">
								<td colspan="2">
									<table>
										<tr class="trait">
											<td >
												{{$order->firstname}} {{$order->lastname}}<br />
												<!-- {{$order->city}}<br /> -->
												{{$order->line1}}
											</td>
										</tr>
										<tr class="trait">
											
											<td style="font-size: 15px;" >
												Nama: PT. PBDIGITAL TECHNOLOGY INDONESIA<br />
												Alamat: Yes Building 6th Floor, Jl. Aipda KS Tubun No. 85, Slipi, Palmerah, Jakarta Barat 11410<br />
												NPWP: 41.823.574.3-031.000
											</td>
										</tr>
									</table>
								</td>
							</tr>

							<tr style="    border: 2px solid #eee;" class="heading">
								<td>Metode Pembayaran</td>
								<td>#Total Harga</td>
							</tr>

							<tr style="    border: 2px solid #eee;" class="details">
								<td>{{App\Models\Payment::where('code',$order->code_booking)->first()->channel_name}}</td>

			          <td>Rp {{strpos($order->total,',') ? substr(str_replace(',','.',$order->total),0,-3) : number_format(intval($order->total),0,',','.') }}</td>
							</tr>
							<tr style="    border: 2px solid #eee;" class="heading">
								<td>Pesanan Diantar/Dikirim</td>
								<td>#Status</td>
							</tr>

							<tr style="    border: 2px solid #eee;" class="details">
								@if($order->shiptype != 'Diambil')
								<td>Di Antarkan ke penginapan<br />{{$order->line1}}</td>
								@else
								<td>Di Ambil di toko agen</td>
								@endif
								@if(App\Models\Payment::where('code',$order->code_booking)->first()->status == 'waiting')
			          <td>Menunggu pembayaran</td>
			          @else
			          <td>{{App\Models\Payment::where('code',$order->code_booking)->first()->status}}</td>
			          @endif
							</tr>

							<tr style="    border: 2px solid #eee;" class="heading">
								<td>Item</td>

								<td>Harga</td>
							</tr>
							@foreach($order->orderItem as $r)
							<tr style="    border: 2px solid #eee;" class="total last">
								<td>{{$r->name_product}} <br>({{$r->quantity}}) pcs</td>

								<td>Rp {{number_format($r->price,0,',','.')}}</td>
							</tr>

							@endforeach

							<!-- <tr class="item">
								<td>Tiket Kapal KM Samudra<br>
			                    	<p style="font-size:12px"> (2 Tiket)<br>
			                        (Marina Ancol ke Pulau Pramuka)<br> 
			                        (Keberangkatan: 20/01/2022 : 10.00 AM)<br>
			                        (Kepulangan: 23/01/2022 : 10.00 AM)</p></td>

								<td>Rp 2.000.000</td>
							</tr>

							<tr class="item last">
								<td>Sewa Sepeda Gunung perhari <br> (2 pcs)</td>

								<td>Rp 50.000</td>
							</tr> -->

							@if($order->status == 'Canceled')
							<tr style="    border: 2px solid #eee;">
								<td>Di Batalkan</td>
							</tr>
							@else
							<tr style="    border: 2px solid #eee;" class="total last">
								<td>Biaya Penanganan:</td>
								<td>Rp {{number_format($order->total-intval(substr(str_replace(',','',$order->subtotal),0,-3))-4000,0,',','.')}}</td>
							</tr>
							<tr style="    border: 2px solid #eee;" class="total last">
								<td>Biaya Transfer</td>
								<td>Rp 4.440</td>
							</tr>
							<tr style="    border: 2px solid #eee;" class="total last">
								<td>Total: </td>
								<td>Rp {{strpos($order->total,',') ? substr(str_replace(',','.',$order->total),0,-3) : number_format(intval($order->total),0,',','.') }}</td>
							</tr>
							@endif
						</table>
						<p style="    margin-top: 20px;font-size: 10px;font-style: italic;">*Invoice ini berlaku sebagai faktur pajak.</p>
					</div>
        </div>
      </div>
</div>
</body>
</html>