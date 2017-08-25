<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\Doc\Adapter;

use Eelly\Annotations\Adapter\AdapterInterface;
use GuzzleHttp\json_encode;
use ReflectionClass;

/**
 * Class ApiDocumentShow.
 */
class ApiDocumentShow extends AbstractDocumentShow implements DocumentShowInterface
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $method;

    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    public function display(): void
    {
        $reflectionClass = new ReflectionClass($this->class);
        $interfaces = $reflectionClass->getInterfaces();
        $interface = array_pop($interfaces);
        $reflectionMethod = $interface->getMethod($this->method);

        $docComment = $this->getDocComment($reflectionMethod->getDocComment());
        $authorsMarkdown = '';
        foreach ($docComment['authors'] as $item) {
            $authorsMarkdown .= sprintf("- %s <%s>\n", $item->getAuthorName(), $item->getEmail());
        }
        $paramsDescriptions = [];
        foreach ($docComment['params'] as $item) {
            $paramsDescriptions[$item->getVariableName()] = (string) $item->getDescription();
        }

        $params = [];
        $paramsMarkdown = 0 == $reflectionMethod->getNumberOfParameters() ? '' : <<<'EOF'
### 请求参数
参数名|类型|是否可选|默认值|说明
-----|----|-----|-------|---

EOF;
        foreach ($reflectionMethod->getParameters() as $key => $value) {
            $name = $value->getName();
            $params[$key] = [
                'name'         => $name,
                'type'         => (string) $value->getType(),
                'allowsNull'   => '否',
                'defaultValue' => ' ',
                'description'  => $paramsDescriptions[$name],
            ];
            if ($value->isDefaultValueAvailable()) {
                $params[$key]['defaultValue'] = $value->getDefaultValue();
                $params[$key]['allowsNull'] = '是';
                $params[$key]['defaultValue'] = preg_replace("/\s/", '', var_export($params[$key]['defaultValue'], true));
            }
            $paramsMarkdown .= sprintf("%s|%s|%s|%s|%s\n",
                $params[$key]['name'],
                $params[$key]['type'],
                $params[$key]['allowsNull'],
                $params[$key]['defaultValue'],
                $params[$key]['description']);
        }
        $methodMarkdown = $this->getFileContent($interface->getFileName(), $reflectionMethod->getStartLine(), 1);
        $methodMarkdown = trim($methodMarkdown);
        if ($this->annotations instanceof AdapterInterface) {
            $this->annotations->delete($reflectionMethod->class);
        }
        $annotations = $this->annotations->getMethod(
            $reflectionMethod->class,
            $reflectionMethod->name
        );

        $requestExample = '';
        if ($annotations->has('requestExample')) {
            $arguments = $annotations->get('requestExample')->getArgument(0);
            $requestExample = <<<EOF
### 请求示例
```\n
EOF;

            if (is_array($arguments)) {
                foreach ($arguments as $key => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                    $requestExample .= $key.':'.$value.PHP_EOL;
                }
            }
            $requestExample .= '```';
        }
        $returnExample = '';
        if ($annotations->has('returnExample')) {
            $returnExample .= "### 返回示例\n```\n";
            $arguments = $annotations->get('returnExample')->getArgument(0);
            $returnExample .= json_encode(['data' => $arguments], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n```";
        }
        $markdown = <<<EOF
## {$docComment['summary']}
```php
$methodMarkdown
```
{$docComment['description']}

$returnExample

$paramsMarkdown

$requestExample

### 代码贡献
$authorsMarkdown
EOF;
        //dd($returnExample);
        $this->echoMarkdownHtml($markdown);
    }
}
