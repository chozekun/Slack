<div id="content">

  <h2>Variable example</h2>
  <div class="layout">

    <h3>Variable example</h3>
    <p>Input:</p>{noparse}
    <pre>{$variable}</pre>
    <p>Output:</p>{/noparse}
    <pre>{$variable}</pre>

    <h3>Variable assignment</h3>
    <p>Input:</p>{noparse}
    <pre>{$number=10}
{$number}</pre>
    <p>Output:</p>{/noparse}
    <pre>{$number=10}
{$number}</pre>

    <h3>Variable assignment using function</h3>
    <p>Input:</p>{noparse}
    <pre>{$number_line=str_pad(" $number ",20,'=',STR_PAD_BOTH)}
{$number_line}</pre>
    <p>Output:</p>{/noparse}
    <pre>{$number_line=str_pad(" $number ",20,'=',STR_PAD_BOTH)}
{$number_line}</pre>

    <h3>Operation with strings</h3>
    <p>Input:</p>{noparse}
    <pre>{$variable . $number}
{$number + 20}</pre>
    <p>Output:</p>{/noparse}
    <pre>{$variable . $number}
{$number + 20}</pre>

    <h3>Variable Modifiers</h3>
    <p>Input:</p>{noparse}
    <pre>{$variable|substr:0,7}
{"hello world"|strtoupper}</pre>
    <p>Output:</p>{/noparse}
    <pre>{$variable|substr:0,7}
{"hello world"|strtoupper}</pre>

  </div>

  <h2>Constants</h2>
  <div class="layout">

    <h3>Constant</h3>
    When using constants with modifiers, there's no need to wrap them with #.
    <p>Input:</p>{noparse}
    <pre>{#PHP_VERSION#}
{PHP_VERSION|trim}</pre>
    <p>Output:</p>{/noparse}
    <pre>{#PHP_VERSION#}
{PHP_VERSION|trim}</pre>

  </div>

  <h2>Loop example</h2>
  <div class="layout">

    <h3>Simple loop example</h3>
    The loop variables are set to <code>$counter</code>, <code>$key</code>, and <code>$value</code>.
    <p>Input:</p>{noparse}
    <pre>{loop="week"}
  index {$counter}: [{$key}] = {$value},
{/loop}</pre>
    <p>Output:</p>{/noparse}
    <pre>{loop="week"}
  index {$counter}: [{$key}] = {$value},
{/loop}</pre>

    <h3>Loop example with associative array</h3>
    <p>Input:</p>{noparse}
    <pre>ID | Name | Color
{loop="user"}
  {$key} | {$value.name|strtoupper} | {$value.color}
{/loop}</pre>
    <p>Output:</p>{/noparse}
    <pre>ID | Name | Color
{loop="user"}
  {$key} | {$value.name|strtoupper} | {$value.color}
{/loop}</pre>

    <h3>Loop an empty array</h3>
    <p>Input:</p>{noparse}
    <pre>{loop="empty_array"}
  {$key} | {$value.name} | {$value.color}
{else}
  The array is empty
{/loop}</pre>
    <p>Output:</p>{/noparse}
    <pre>{loop="empty_array"}
  {$key} | {$value.name} | {$value.color}
{else}
  The array is empty
{/loop}</pre>

  </div>

  <h2>If Example</h2>
  <div class="layout">

    <h3>simple if example</h3>
    <p>Input:</p>{noparse}
    <pre>{if="$number==10"}
  OK!
{else}
  NO!
{/if}</pre>
    <p>Output:</p>{/noparse}
    <pre>{if="$number==10"}
  OK!
{else}
  NO!
{/if}</pre>

    <h3>example of if, elseif, else example</h3>
    <p>Input:</p>{noparse}
    <pre>{if="substr($variable,0,1)=='A'"}
  First character is A
{elseif="substr($variable,0,1)=='B'"}
  First character is B
{else}
  First character of variable is not A neither B
{/if}</pre>
    <p>Output:</p>{/noparse}
    <pre>{if="substr($variable,0,1)=='A'"}
  First character is A
{elseif="substr($variable,0,1)=='B'"}
  First character is B
{else}
  First character of variable is not A neither B
{/if}</pre>

    <h3>use of ? : operator</h3>
    <p>You can also use the ? operator instead of if</p>
    <p>Input:</p>{noparse}
    <pre>{$number==10 ? 'OK!' : 'NO!'}</pre>
    <p>Output:</p>{/noparse}
    <pre>{$number==10 ? 'OK!' : 'NO!'}</pre>

  </div>

  <h2>Functions</h2>
  <div class="layout">

    <h3>Example of function</h3>
    <p>Input:</p>{noparse}
    <pre>{function="ucfirst(strtolower($variable))"}</pre>
    <p>Output:</p>{/noparse}
    <pre>{function="ucfirst(strtolower($variable))"}</pre>

  </div>

</div>
