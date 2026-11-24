<?php

namespace Tests\Feature;

use App\Support\PhotoLibrary;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoLibraryTest extends TestCase
{
    public function test_every_visual_uses_a_valid_local_photograph(): void
    {
        foreach (['restaurant', 'plat', 'evenement', 'article', 'danse', 'kora', 'balafon', 'djembe', 'dj', 'jazz', 'conte', 'peinture', 'mc', 'tissage', 'poterie', 'sculpture', 'vannerie', 'bijoux', 'calebasse', 'maroquinerie', 'unknown'] as $visual) {
            $url = media_url(null, $visual, 'example');
            $this->assertStringNotContainsString('/illustration/', $url);
            $this->assertSame($url, media_url(null, $visual, 'example'));
            $this->assertStringContainsString('/assets/img/', $url);
            $path = public_path('assets/img/'.explode('/assets/img/', $url, 2)[1]);
            $this->assertFileExists($path);
            $this->assertContains(getimagesize($path)[2], [IMAGETYPE_JPEG, IMAGETYPE_WEBP]);
        }
    }

    public function test_compressed_gallery_variants_are_valid_and_responsive(): void
    {
        foreach (['8k2a0148', '8k2a0270', '8k2a0318', '8k2a0378', '8k2a8853', '8k2a8873', '8k2a8889', '8k2a8903', '8k2a8916', '8k2a9994', 'img_0192'] as $name) {
            foreach ([640, 1280] as $width) {
                $path = public_path("assets/img/photos/galerie-$name-$width.webp");
                $this->assertFileExists($path);
                [$actualWidth, $height, $type] = getimagesize($path);
                $this->assertSame($width, $actualWidth);
                $this->assertSame(IMAGETYPE_WEBP, $type);
                $this->assertLessThan(400000, filesize($path));
            }
            $url = asset("assets/img/photos/galerie-$name-1280.webp");
            $this->assertSame(asset("assets/img/photos/galerie-$name-640.webp").' 640w, '.$url.' 1280w', PhotoLibrary::srcset($url));
        }
        $this->assertNull(PhotoLibrary::srcset('https://example.com/photo.jpg'));
        $this->assertNull(PhotoLibrary::srcset('https://example.com/galerie-photo-1280.webp'));
    }

    public function test_all_catalogued_dishes_have_their_own_photograph(): void
    {
        $this->assertCount(11, PhotoLibrary::DISHES);
        foreach (PhotoLibrary::DISHES as $slug => $file) {
            $this->assertSame(asset('assets/img/'.$file), media_url(null, 'plat', $slug));
            $this->assertFileExists(public_path('assets/img/'.$file));
        }
    }

    public function test_uploaded_and_external_media_remain_prioritary(): void
    {
        $path = 'restaurants/my-photo.jpg';
        $this->assertSame(Storage::disk('public')->url($path), media_url($path, 'restaurant', 'example'));
        $this->assertSame('https://example.com/photo.jpg', media_url('https://example.com/photo.jpg', 'restaurant', 'example'));
    }

    public function test_every_credited_photograph_exists_and_is_valid(): void
    {
        foreach (array_keys(PhotoLibrary::PEXELS + PhotoLibrary::COMMONS) as $file) {
            $path = public_path('assets/img/'.$file);
            $this->assertFileExists($path);
            $this->assertSame(IMAGETYPE_JPEG, getimagesize($path)[2]);
        }
    }
}
