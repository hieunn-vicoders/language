<?php
namespace VCComponent\Laravel\Language\Http\Controllers\Api\Admin;

use Exception;
use Illuminate\Http\Request;
use VCComponent\Laravel\Language\Repositories\LanguageableRepository;
use VCComponent\Laravel\Language\Transformers\LanguageableTransformer;
use VCComponent\Laravel\Vicoders\Core\Controllers\ApiController;

class LanguageableController extends ApiController
{
    protected $languageable_repository;
    protected $langaugeable_entity;
    protected $languageable_transformer;
    protected $language_entity;
    public function __construct(
        LanguageableRepository $languageable_repository,
        LanguageableTransformer $languageable_transformer
    ) {
        $this->languageable_repository = $languageable_repository;
        $this->languageable_transformer = $languageable_transformer;
        $this->languageable_entity = $this->languageable_repository->getEntity();
    }

    public function applyMetaFromRequest($query, $request)
    {
        if ($request->has('meta')) {
            $meta = (array) json_decode($request->get('meta'));
            if (count($meta)) {
                $languageable_id_array = explode(',', $meta['id_meta']);
                $query = $query
                    ->whereIn('languageable_id', $languageable_id_array)
                    ->where('languageable_type', $meta['type_meta']);
            }
            return $query;
        }
        return;
    }

    public function store(Request $request)
    {
        $data = $request->all();
        foreach ($data as $value) {

            $check_translate_exists = $this->languageable_repository->checkTranslateExists($value);

            if ($check_translate_exists) {

                throw new Exception('Bản dịch đã tồn tại');
            }

            $this->languageable_repository->create($value);
        }

        return $this->success();
    }

    public function update(Request $request)
    {
        $data = $request->all();
        foreach ($data as $value) {
            $this->languageable_repository->updateTranslateRecord($value);
        }

        return $this->success();
    }

    function list(Request $request) {
        $query = $this->languageable_entity;
        $query_meta = $this->languageable_entity;

        $query_languageable = $this->applyConstraintsFromRequest($query, $request);
        $languageable = $query_languageable->get();

        $query_languageable_meta = $this->applyMetaFromRequest($query_meta, $request);
        if (!$query_languageable_meta) {
            return $this->response->collection($languageable, $this->languageable_transformer);
        }

        $languageable_meta = $query_languageable_meta->get();
        return $this->response->collection($languageable->merge($languageable_meta), $this->languageable_transformer);
    }
}
