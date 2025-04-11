<?php
/**
 * This is the template for generating the base model class for a module.
 */

echo "<?php\n";
?>
namespace <?= $generator->moduleID ?>\hooks;

/**
 * This is the base model class for <?= $generator->moduleID ?> module.
 *
 * @OA\Info(
 *     description="API documentation for <?= $generator->moduleID ?> module",
 *     version="1.0.0",
 *     title="<?= $generator->moduleID ?> Module",
 *     @OA\Contact(
 *         email="douglasdaggs@gmail.com",
 *         name="Ananda Douglas"
 *     )
 * )
 */  

 /**
 * @OA\SecurityScheme(securityScheme="bearerAuth",type="http",scheme="bearer",bearerFormat="JWT")
 * @OA\SecurityScheme(securityScheme="cookieAuth",type="http",in="cookie",scheme="bearer",name="refresh-token")
 * @OA\OpenApi(security={{"bearerAuth":{}}})
 * */

/**
 * @OA\Tag(
 *     name="<?=strtoupper($generator->moduleID) ?>",
 *     description="Endpoints for the <?= strtoupper($generator->moduleID) ?> module"
 * )
 */

/**
 * @OA\Get(path="/about",
 *   summary="Module Info. ",
 *   tags={"<?= strtoupper($generator->moduleID) ?>"},
 *   security={{}},
 *   @OA\Response(
 *     response=200,
 *     description="success",
 *      @OA\JsonContent(
 *          @OA\Property(property="data", type="array",@OA\Items(ref="#/components/schemas/About")),
 *          
 *      )
 *   ),
 * )
 */

/**
 *@OA\Schema(
 *  schema="About",
 *  @OA\Property(property="id", type="string",title="Module ID", example="<?= strtoupper($generator->moduleID) ?>"),
 *  @OA\Property(property="name", type="string",title="Module Name", example="<?= strtoupper($generator->moduleID) ?> Module"),
 * )
 */
class BaseModel extends \yiitron\novakit\ActiveRecord
{
    
}
