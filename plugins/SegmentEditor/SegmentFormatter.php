<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\SegmentEditor;

use Piwik\Config;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Segment\SegmentExpression;

/**
 */
class SegmentFormatter
{
    /**
     * @var SegmentList
     */
    private $segmentList;

    private $matchesMetric = array(
        SegmentExpression::MATCH_EQUAL => 'General_OperationEquals',
        SegmentExpression::MATCH_NOT_EQUAL => 'General_OperationNotEquals',
        SegmentExpression::MATCH_LESS_OR_EQUAL => 'General_OperationAtMost',
        SegmentExpression::MATCH_GREATER_OR_EQUAL => 'General_OperationAtLeast',
        SegmentExpression::MATCH_LESS => 'General_OperationLessThan',
        SegmentExpression::MATCH_GREATER => 'General_OperationGreaterThan',
    );

    private $matchesDimension = array(
        SegmentExpression::MATCH_EQUAL => 'General_OperationIs',
        SegmentExpression::MATCH_NOT_EQUAL => 'General_OperationIsNot',
        SegmentExpression::MATCH_CONTAINS => 'General_OperationContains',
        SegmentExpression::MATCH_DOES_NOT_CONTAIN => 'General_OperationDoesNotContain',
        '=^' => 'General_OperationStartsWith',
        '=$' => 'General_OperationEndsWith'
    );

    private $operators = array(
        SegmentExpression::BOOL_OPERATOR_AND => 'General_And',
        SegmentExpression::BOOL_OPERATOR_OR => 'General_Or',
        SegmentExpression::BOOL_OPERATOR_END => '',
    );

    public function __construct(SegmentList $segmentList)
    {
        $this->segmentList = $segmentList;
    }

    public function getHumanReadable($segmentString, $idSite)
    {
        if (empty($segmentString)) {
            return Piwik::translate('SegmentEditor_DefaultAllVisits');
        }

        $segment = new SegmentExpression($segmentString);
        $expressions = $segment->parseSubExpressions();

        $readable = '';
        foreach ($expressions as $expression) {
            $operator = $expression[SegmentExpression::INDEX_BOOL_OPERATOR];
            $operand  = $expression[SegmentExpression::INDEX_OPERAND];

            $segment = $this->segmentList->findSegment($operand[SegmentExpression::INDEX_OPERAND_NAME], $idSite);

            if (empty($segment)) {
                continue;
            }

            $readable .= $segment['name'] . ' ';
            $readable .= $this->getTranslationForComparison($operand, $segment['type']) . ' ';
            $readable .= $operand[SegmentExpression::INDEX_OPERAND_VALUE] . ' ';
            $readable .= $this->getTranslationForBoolOperator($operator) . ' ';
        }

        return $readable;
    }

    private function getTranslationForComparison($operand, $segmentType)
    {
        $operator = $operand[SegmentExpression::INDEX_OPERAND_OPERATOR];

        $translation = $operator;

        if ($segmentType === 'dimension' && !empty($this->matchesDimension[$operator])) {
            $translation = Piwik::translate($this->matchesDimension[$operator]);
        }
        if ($segmentType === 'metric' && !empty($this->matchesMetric[$operator])) {
            $translation = Piwik::translate($this->matchesMetric[$operator]);
        }

        return $translation;
    }

    private function getTranslationForBoolOperator($operator)
    {
        $translation = '';

        if (!empty($operators[$operator])) {
            $translation = Piwik::translate($operators[$operator]);
        } elseif (!empty($operator)) {
            $translation = $operator;
        }

        return $translation;
    }
}
