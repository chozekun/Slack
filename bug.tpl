[{$project.value} {$id.value}]: {$summary.value}
{$sep=str_pad("",70,"=")}{$sep2=str_pad("",70,"-")}{$sep}
{$event}
{$sep}
{$url.value}
{$sep}
{$history.fields.date} | {$history.fields.username} | {$history.fields.note} | {$history.fields.change}
{$sep2}{$value=end($history.values)}
{$value.date} | {$value.username} | {$value.note} | {$value.change}
{$sep}
{$reporter.field}: {$reporter.value}
{$handler.field}: {$handler.value}
{$sep}
{$project.field}: {$project.value}
{$id.field}: {$id.value}
{$category.field}: {$category.value}
{$tag.field}: {function="join(', ', $tag.values)"}
{$reproducibility.field}: {$reproducibility.value}
{$severity.field}: {$severity.value}
{$priority.field}: {$priority.value}
{$status.field}: {$status.value}
{$due_date.field}: {$due_date.value}
{$version.field}: {$version.value}
{$target_version.field}: {$target_version.value}
{$resolution.field}: {$resolution.value}
{$fixed_in_version.field}: {$fixed_in_version.value}
{$sep}
{if="$custom_fields"}
{loop="custom_fields"}
{$value.field}: {$value.value}
{/loop}
{$sep}
{/if}
{$date_submitted.field}: {$date_submitted.value}
{$last_update.field}: {$last_update.value}
{$sep}
{$summary.field}: {$summary.value}
{$description.field}:
{$description.value}
{$steps_to_reproduce.field}:
{$steps_to_reproduce.value}
{$additional_information.field}:
{$additional_information.value}
{$sep}
{if="$relationships.values"}
{$relationships.fields.relationship} | {$relationships.fields.id} | {$relationships.fields.summary}
{$sep2=str_pad("",70,"-")}{$sep2}
{loop="relationships.values"}
{$value.relationship} | {$value.id} | {$value.summary}
{/loop}
{$sep}
{/if}
{$history.title}
{$history.fields.date} | {$history.fields.username} | {$history.fields.note} | {$history.fields.change}
{$sep2}
{$context=4}{if="count($history.values) > $context * 2"}
{$oldest_history=array_slice($history.values, 0, $context)}{loop="oldest_history"}
{$value.date} | {$value.username} | {$value.note} | {$value.change}
{/loop}
...
{$recent_history=array_slice($history.values, -$context)}{loop="recent_history"}
{$value.date} | {$value.username} | {$value.note} | {$value.change}
{/loop}
{else}
{loop="history.values"}
{$value.date} | {$value.username} | {$value.note} | {$value.change}
{/loop}
{/if}
{$sep}
