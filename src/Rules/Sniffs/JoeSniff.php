<?php declare(strict_types = 1);

namespace DrupalCheck\Rules\Sniffs;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class JoeSniff implements \PHPStan\Rules\Rule
{

    public function getNodeType(): string
    {
        return \PhpParser\Node\FunctionLike::class;
    }

    /**
     * @param \PhpParser\Node\FunctionLike $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        $errors[] = 'ERROR!';
        return $errors;
    }
}
