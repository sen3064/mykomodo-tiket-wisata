<?php

namespace App\Http\Controllers\Api\Pemda;

use App\Http\Controllers\Controller;
use App\Models\AboutKabupaten;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Media;

class PemdaAPIController extends Controller
{
    protected $prod = false;
    protected $token;

    public function index()
    {
        $k = AboutKabupaten::find(2);
        // dd($k);
        if($k){
            $preurl = $this->prod ? "https://cdn.kolaka.kabtour.com/uploads/" : "https://cdn.kolaka.kabtour.com/uploads/";
            $banner = Media::find($k->banner_id);
            $logo = Media::find($k->logo_id);
            $k->logo = $logo ? [
                "original"  => $preurl . $logo->file_path,
                "200x150"   => $preurl . $logo->file_resize_200,
                "250x200"   => $preurl . $logo->file_resize_250,
                "400x350"   => $preurl . $logo->file_resize_400,
            ] : null;
            $k->banner = $banner ? [
                "original"  => $preurl . $banner->file_path,
                "200x150"   => $preurl . $banner->file_resize_200,
                "250x200"   => $preurl . $banner->file_resize_250,
                "400x350"   => $preurl . $banner->file_resize_400,
            ] : null;
            $gall = explode(',', $k->gallery) ?? null;
            $gallimg = [];
            if ($gall) {
                foreach ($gall as $key => $v) {
                    $img = Media::find($v);
                    if ($img) {
                        $arrimg = [
                            "original"  => $preurl . $img->file_path,
                            "200x150"   => $preurl . $img->file_resize_200,
                            "250x200"   => $preurl . $img->file_resize_250,
                            "400x350"   => $preurl . $img->file_resize_400,
                        ];
                        array_push($gallimg, $arrimg);
                    }
                }
            }
    
            $k->gallery = $gallimg;
        }
        return response()->json(["success" => true, "data" => $k], 200);
    }

    public function store(Request $request)
    {
        $this->token = $request->bearerToken();
        $check = AboutKabupaten::all()->count();
        if ($check > 0) {
            return response()->json(["success" => false, "message" => "cannot add new data"], 401);
        }
        $kab = AboutKabupaten::create($request->except(['banner', 'gallery', 'logo']));
        if (!$kab->id) {
            return response()->json(['message' => 'Failed to Save Tentang Kabupaten'], 500);
        }
        $cdn = $this->prod ? "https://cdn.kolaka.kabtour.com/v2/media_files" : "https://cdn.kolaka.kabtour.com/v2/media_files";
        $post = Http::withToken($this->token)->withoutVerifying()->withOptions(["verify" => false])->acceptJson();
        $names = [];
        foreach ($request->allFiles() as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $vk) {
                    $name = $vk->getClientOriginalName();
                    $post->attach($k . '[]', file_get_contents($vk), $name);
                    $names[] = $name;
                }
            } else {
                $name = $v->getClientOriginalName();
                $post->attach($k, file_get_contents($v), $name);
                $names[] = $name;
            }
        }
        // dd($names);

        $response = $post->post($cdn, ["prefix" => "tentang-kabupaten"]);
        // dd($response->json());
        // dd($response->json());
        $result = json_decode(json_encode($response->json()));
        $banner = 0;
        $gallery = [];

        $logo = $result->logo->id;
        $banner = $result->banner->id;
        if (isset($result->gallery)) {
            for ($i = 0; $i < sizeof($result->gallery); $i++) {
                $gallery[] = $result->gallery[$i]->id;
            }
        }

        $kab->logo_id = $logo;
        $kab->banner_id = $banner;
        $kab->gallery = implode(',', $gallery);
        $kab->save();

        return response()->json(["success" => true, "message" => "Data berhasil disimpan", "data" => $kab], 200);
    }

    public function update($id, Request $request)
    {
        $kab = AboutKabupaten::find($id);
        if ($kab) {
            $kab->update($request->except(['banner', 'gallery', 'logo', '_method']));
            $this->token = $request->bearerToken();

            $cdn = $this->prod ? "https://cdn.kolaka.kabtour.com/v2/media_files" : "https://cdn.kolaka.kabtour.com/v2/media_files";
            $post = Http::withToken($this->token)->withoutVerifying()->withOptions(["verify" => false])->acceptJson();
            $names = [];
            foreach ($request->allFiles() as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $vk) {
                        $name = $vk->getClientOriginalName();
                        $post->attach($k . '[]', file_get_contents($vk), $name);
                        $names[] = $name;
                    }
                } else {
                    $name = $v->getClientOriginalName();
                    $post->attach($k, file_get_contents($v), $name);
                    $names[] = $name;
                }
            }
            // dd($names);
            $result = '';
            if (sizeof($names) > 0) {
                $response = $post->post($cdn, ["prefix" => "tentang-kabupaten"]);
                // dd($response->json());
                // dd($response->json());
                $result = json_decode(json_encode($response->json()));
                $logo = 0;
                $banner = 0;
                $gallery = [];

                $data = '';
                if (isset($result->logo)) {
                    $logo = $result->logo->id;
                    $kab->logo_id = $logo;
                }
                if (isset($result->banner)) {
                    $banner = $result->banner->id;
                    $kab->banner_id = $banner;
                }
                if (isset($result->gallery)) {
                    for ($i = 0; $i < sizeof($result->gallery); $i++) {
                        $gallery[] = $result->gallery[$i]->id;
                    }
                    $kab->gallery = implode(',', $gallery);
                }
                $kab->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diubah',
                'data' => $kab,
            ]);
        }
        return response()->json(
            [
                'success' => false,
                'message' => 'Data tidak ditemukan',
                'data' => null,
            ]
        );
    }
}
