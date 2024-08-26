[{$project.value} {$id.value}]: {$summary.value}
{$sep=str_pad("",70,"-")}{$sep}
{$event}
{$sep}
 ({$bugnote.id.value}) {$bugnote.reporter.value} ({$bugnote.access_level.value}) - {$bugnote.last_modified.value} ({$bugnote.view_state.value})
 {$bugnote.url.value}
{$sep}
{$bugnote.note.value}
{$sep}
{if="$bugnote.files.values"}
{$bugnote.files.field}
{loop="bugnote.files.values"}
- {$value.name} ({$value.size|number_format} {$value.size_unit})
{/loop}
{$sep}
{/if}
