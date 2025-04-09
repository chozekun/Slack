[{$project.value} {$id.value}]: {$summary.value}
{$sep=str_pad("",70,"=")}{$sep2=str_pad("",70,"-")}{$sep}
{$event}
{$sep}
{$url.value}
{$sep}
{$id.field}: {$id.value} | {$project.field}: {$project.value} | {$category.field}: {$category.value} | {$view_state.field}: {$view_state.value}
{$date_submitted.field}: {$date_submitted.value} | {$last_update.field}: {$last_update.value}
{$sep}
{$reporter.field}: {$reporter.value} | {$handler.field}: {$handler.value}
{$priority.field}: {$priority.value} | {$severity.field}: {$severity.value} | {$reproducibility.field}: {$reproducibility.value}
{$status.field}: {$status.value} | {$resolution.field}: {$resolution.value}
{$version.field}: {$version.value} | {$target_version.field}: {$target_version.value} | {$fixed_in_version.field}: {$fixed_in_version.value}
{$sep}
{$history.fields.date} | {$history.fields.username} | {$history.fields.note} | {$history.fields.change}
{$sep2}{$value=end($history.values)}
{$value.date} | {$value.username} | {$value.note} | {$value.change}
{$sep}
