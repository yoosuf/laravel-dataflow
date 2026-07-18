<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\Enums\ImportFormat;
use Yoosuf\LaravelDataFlow\Http\Requests\MappingPreviewRequest;
use Yoosuf\LaravelDataFlow\Importing\Mapping\MappingPreviewService;

final class MappingPreviewController extends Controller
{
    public function __invoke(MappingPreviewRequest $request, MappingPreviewService $preview): JsonResponse
    {
        $source = new ImportSource(
            disk: (string) $request->string('disk'),
            path: (string) $request->string('path'),
            format: ImportFormat::from((string) $request->string('format')),
        );

        $data = $preview->preview($source, (int) $request->integer('sample_size', 5));

        return response()->json($data);
    }
}
