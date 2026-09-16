<?php

namespace tests\unit;

use backend\modules\export\controllers\ExportController;
use PHPUnit\Framework\TestCase;

/**
 * The guard on values written into an export.
 *
 * Box\Spout writes what it is given, so a record whose text starts with =, +, - or @
 * becomes a live formula when the recipient opens the file - that is how a customer name
 * turns into a lookup into the rest of the sheet, or a link somewhere else.
 *
 * The numeric exemption is the part worth pinning: amounts arrive from DECIMAL columns
 * as strings, and a negative one starts with a trigger character. Quoting those would
 * turn every "-19.04" into text and break the totals in the invoice exports, which is a
 * regression the first version of this guard actually had.
 */
class SpreadsheetFormulaTest extends TestCase
{
	/**
	 * The guard is a protected method on the controller rather than a helper, since the
	 * two export controllers are the only writers.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function neutralize($value)
	{
		$method = new \ReflectionMethod(ExportController::class, 'neutralizeFormula');
		$method->setAccessible(true);

		return $method->invoke(
			(new \ReflectionClass(ExportController::class))->newInstanceWithoutConstructor(),
			$value
		);
	}

	/**
	 * @return array
	 */
	public function formulaProvider()
	{
		return [
			'equals' => ['=1+1'],
			'plus with text' => ['+41712 abc'],
			'minus with text' => ['-2+3a'],
			'at' => ['@SUM(A1)'],
			'leading spaces' => ['  =cmd|calc'],
			'hyperlink' => ['=HYPERLINK("http://example.test","click")'],
			'full-width equals' => ["\u{FF1D}1+1"],
			'full-width plus' => ["\u{FF0B}1+1"],
			'full-width at' => ["\u{FF20}SUM(A1)"],
		];
	}

	/**
	 * @dataProvider formulaProvider
	 * @param string $value
	 */
	public function testFormulaBearingValuesAreMarkedAsText($value)
	{
		$this->assertStringStartsWith("'", (string) $this->neutralize($value),
			'a value a spreadsheet would evaluate must be pinned to text');
	}

	/**
	 * @return array
	 */
	public function numberProvider()
	{
		return [
			'negative amount' => ['-19.04'],
			'negative fraction' => ['-0.5'],
			'plain integer' => ['1234'],
			'decimal' => ['19.04'],
			'E.164 phone' => ['+40712345678'],
			'exponent' => ['-1.2e3'],
		];
	}

	/**
	 * @dataProvider numberProvider
	 * @param string $value
	 */
	public function testNumbersPassThroughUntouched($value)
	{
		$this->assertSame($value, $this->neutralize($value),
			'a number cannot carry an expression, and quoting it breaks the totals');
	}

	public function testOrdinaryTextIsLeftAlone()
	{
		foreach (['Timisoara', 'a=b', 'Str. Exemplu 10', ''] as $value) {
			$this->assertSame($value, $this->neutralize($value));
		}
	}

	public function testNonStringsSurvive()
	{
		$this->assertNull($this->neutralize(null));
		$this->assertSame(19.04, $this->neutralize(19.04));
		$this->assertSame(-3, $this->neutralize(-3));
		$this->assertTrue($this->neutralize(true));
	}

	/**
	 * Both export controllers carry the same copy; they must not drift apart.
	 */
	public function testTheWorkspaceExportCarriesTheSameGuard()
	{
		$source = file_get_contents(
			dirname(__DIR__, 3) . '/workspace/backend/modules/export/controllers/ExportController.php'
		);

		$this->assertStringContainsString('function neutralizeFormula', $source);
		$this->assertStringContainsString('is_numeric($trimmed)', $source,
			'the workspace copy must exempt numbers too, or its invoice totals break');
	}
}
