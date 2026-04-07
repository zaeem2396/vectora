<?php

declare(strict_types=1);

namespace Vectora\Pinecone\Tests\Feature\Laravel;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

final class MakeVectorModelCommandTest extends PineconeFeatureTestCase
{
    public function test_generates_embeddable_model_stub(): void
    {
        $path = app_path('Models/VectoraGeneratedPost.php');
        if (File::exists($path)) {
            File::delete($path);
        }

        $exit = Artisan::call('make:vector-model', ['name' => 'VectoraGeneratedPost', '--force' => true]);

        $this->assertSame(0, $exit);
        $this->assertFileExists($path);
        $contents = File::get($path);
        $this->assertStringContainsString('AbstractEmbeddableModel', $contents);
        $this->assertStringContainsString('vectorEmbeddingFields', $contents);

        File::delete($path);
    }
}
