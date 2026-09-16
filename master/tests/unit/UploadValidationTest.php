<?php

namespace tests\unit;

use frontend\modules\account\models\CompanyForm;
use frontend\modules\account\models\ProfileForm;
use frontend\modules\account\models\SupportTicketForm;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\UploadedFile;

/**
 * The upload allow-list on the forms that write into the web-served uploads tree.
 *
 * Ported from the masteranunturi security branch, which found the same shape here: the
 * rules were present on some forms and still did nothing, because nothing populated
 * imageFile/attachmentFiles before validation. The validator sat on a null attribute,
 * skipOnEmpty let it through, and saveFiles() then pulled the raw upload out of the
 * request under whatever extension the client had chosen. These cases cover both halves
 * - that load() populates the attribute, and that the populated value is checked -
 * because either one alone leaves the hole open.
 */
class UploadValidationTest extends TestCase
{
	/**
	 * @var string[] Temp files to clean up.
	 */
	private $tempFiles = [];

	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $path) {
			if (is_file($path)) {
				unlink($path);
			}
		}
		$this->tempFiles = [];
		UploadedFile::reset();
		parent::tearDown();
	}

	/**
	 * A real file on disk, so the validator's content sniffing has something to read.
	 *
	 * @param string $contents
	 * @param string $suffix
	 * @return string the temp path
	 */
	private function tempFile($contents, $suffix)
	{
		// tempnam() creates the base file too, so both paths are tracked for cleanup.
		$base = tempnam(sys_get_temp_dir(), 'upload-test');
		$path = $base . $suffix;
		file_put_contents($path, $contents);
		$this->tempFiles[] = $base;
		$this->tempFiles[] = $path;

		return $path;
	}

	/**
	 * A one-pixel PNG - valid image bytes, so only the extension is in question.
	 *
	 * @return string
	 */
	private function pngBytes()
	{
		return base64_decode(
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
		);
	}

	/**
	 * @param string $name the client-supplied file name
	 * @param string $contents
	 * @return UploadedFile
	 */
	private function upload($name, $contents)
	{
		$extension = '.' . pathinfo($name, PATHINFO_EXTENSION);
		$path = $this->tempFile($contents, $extension);

		return new UploadedFile([
			'name' => $name,
			'tempName' => $path,
			'type' => 'image/png',
			'size' => strlen($contents),
			'error' => UPLOAD_ERR_OK,
		]);
	}

	public function testAPhpScriptIsRejectedAsAProfileImage()
	{
		$model = new ProfileForm();
		$model->imageFile = $this->upload('shell.php', '<?php echo shell_exec($_GET["c"]); ?>');

		$model->validate(['imageFile']);

		$this->assertTrue(
			$model->hasErrors('imageFile'),
			'an executable extension must not reach the uploads tree'
		);
	}

	public function testAPhpScriptRenamedToPngIsStillRejected()
	{
		$model = new ProfileForm();
		// The extension is on the allow-list; only the content check catches this one.
		$model->imageFile = $this->upload('avatar.png', '<?php echo shell_exec($_GET["c"]); ?>');

		$model->validate(['imageFile']);

		$this->assertTrue(
			$model->hasErrors('imageFile'),
			'the declared extension must match the real content'
		);
	}

	public function testARealImageIsAccepted()
	{
		$model = new ProfileForm();
		$model->imageFile = $this->upload('avatar.png', $this->pngBytes());

		$model->validate(['imageFile']);

		$this->assertFalse($model->hasErrors('imageFile'), implode(', ', $model->getErrors('imageFile')));
	}

	public function testCompanyLogosAreCheckedToo()
	{
		$model = new CompanyForm();
		$model->imageFile = $this->upload('shell.php', '<?php echo 1; ?>');

		$model->validate(['imageFile']);

		$this->assertTrue($model->hasErrors('imageFile'));
	}

	public function testSupportTicketAttachmentsRejectExecutables()
	{
		$model = new SupportTicketForm();
		$model->attachmentFiles = [$this->upload('shell.php', '<?php echo 1; ?>')];

		$model->validate(['attachmentFiles']);

		$this->assertTrue($model->hasErrors('attachmentFiles'));
	}

	public function testTheImageAllowListIsConfigured()
	{
		// Several forms read these params; they were undefined here, which silently
		// turned their validators into no-ops.
		$this->assertNotEmpty(Yii::$app->params['image.extensions']);
		$this->assertNotEmpty(Yii::$app->params['image.mimeTypes']);
		$this->assertNotContains('php', Yii::$app->params['image.extensions']);
		$this->assertNotContains('php', Yii::$app->params['file.extensions']);
	}

	public function testUploadAttributesArePopulatedByLoad()
	{
		// Without this the rules above never see a value, which is how the bypass worked.
		foreach ([ProfileForm::class, CompanyForm::class, SupportTicketForm::class] as $class) {
			$reflection = new \ReflectionMethod($class, 'load');
			$this->assertSame(
				$class,
				$reflection->getDeclaringClass()->getName(),
				"{$class} must declare its own load() so the file validator has something to check"
			);
		}
	}

	public function testTheProfileUploadEndpointOnlyWritesTheAvatarColumn()
	{
		// hasAttribute() accepted any column on `user`, so the caller could name
		// auth_key or password_hash and have the stored file name written into it.
		$allowed = \frontend\modules\account\controllers\ProfileController::UPLOADABLE_ATTRIBUTES;

		$this->assertSame(['image'], $allowed);
		$this->assertNotContains('auth_key', $allowed);
		$this->assertNotContains('password_hash', $allowed);
	}
}
