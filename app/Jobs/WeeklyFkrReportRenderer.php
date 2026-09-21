<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

final class WeeklyFkrReportRenderer
{
    private const TITLES = [
        'plannedOpenings'=>'Плановые открытия',
        'plannedClosings'=>'Плановые закрытия',
        'progress'=>'Прогресс за прошедшую неделю',
        'overdue'=>'Просрочка',
        'attention'=>'Обратить внимание',
    ];
    private const SUMMARY_LABELS = [
        'plannedOpenings'=>'Открытия',
        'plannedClosings'=>'Закрытия',
        'progress'=>'Прогресс',
        'overdue'=>'Просрочка',
        'attention'=>'Внимание',
    ];

    public function render(array $report): array
    {
        $generated=(new \DateTimeImmutable($report['generatedAtUtc']))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('d.m.Y H:i').' Europe/Moscow';
        $start=(new \DateTimeImmutable($report['periods']['planStart']))->format('d.m.Y');$end=(new \DateTimeImmutable($report['periods']['planEnd']))->format('d.m.Y');
        $subject='FMonitor — недельный отчёт ФКР, '.$start.'–'.$end;
        $period='Период планов: '.$start.'–'.$end;
        $summaryCounts=[];
        foreach(self::SUMMARY_LABELS as$key=>$label)$summaryCounts[$key]=count($report['sections'][$key]??[]);
        $summaryText=[];
        foreach(self::SUMMARY_LABELS as$key=>$label)$summaryText[]=$label.' — '.$summaryCounts[$key];
        $text=['Еженедельный отчёт ФКР',$period,'Сформировано: '.$generated,'','Сводка: '.implode(' · ',$summaryText),''];$sections='';
        foreach(self::TITLES as$key=>$title){
            $text[]=$title;$rows=$report['sections'][$key]??[];
            $body='';
            if($rows===[]){
                $text[]='Данных за период нет';
                $empty='<span data-status-label="Данных за период нет" '
                    .'style="display:inline-block;color:#46515e;background-color:#eef0f4;padding:2px 5px;font-weight:600;">'
                    .'Данных за период нет</span>';
                $body=$this->cell($empty);
            }
            foreach($rows as$row){
                $line=$this->line($key,$row);
                $text[]=$line.' — '.$row['url'];
                $body.=$this->cell($this->rowHtml($key,$row));
            }
            $text[]='';
            $heading='<h2 data-status-label="'.$title.'" '
                .'style="margin:0;color:#ffffff;background-color:#0b1623;font-size:14px;line-height:1.3;">'
                .$title.'</h2>';
            $sections.='<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" '
                .'data-report-section="'.$key.'" style="width:100%;border-collapse:collapse;margin:0 0 6px;">'
                .$this->cell($heading,'#0b1623').$body.'</table>';
        }
        $html=$this->document($subject,$period,$generated,$this->summary($summaryCounts),$sections);
        $text[]='Письмо сформировано автоматически. Отвечать на него не нужно.';
        return compact('subject','html')+['text'=>implode("\n",$text)];
    }
    private function document(string$subject,string$period,string$generated,string$summary,string$sections):string
    {
        return '<!doctype html><html lang="ru"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$this->esc($subject).'</title></head>'
            .'<body style="margin:0;padding:0;background-color:#f4f6f9;color:#0b1623;font-family:Arial, Helvetica, sans-serif;font-size:14px;line-height:1.4;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;background-color:#f4f6f9;"><tr>'
            .'<td width="100%" align="center" valign="top" style="width:100%;padding:6px 1px;background-color:#f4f6f9;color:#0b1623;">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" '
            .'style="width:100%;max-width:680px;border-collapse:collapse;background-color:#ffffff;"><tr>'
            .'<td width="100%" align="left" valign="top" style="width:100%;padding:10px 12px;background-color:#253d98;color:#ffffff;">'
            .'<h1 style="margin:0 0 2px;font-size:20px;line-height:1.2;font-weight:600;">'
            .'Еженедельный отчёт ФКР</h1>'
            .'<p style="margin:0;color:#ffffff;">'.$period.'<br>Сформировано: '.$generated.'</p></td></tr><tr>'
            .'<td width="100%" align="left" valign="top" style="width:100%;padding:6px 10px;background-color:#ffffff;color:#0b1623;">'
            .$summary.$sections.'<p style="margin:0;color:#697586;font-size:12px;line-height:1.3;">'
            .'Письмо сформировано автоматически. Отвечать на него не нужно.</p>'
            .'</td></tr></table></td></tr></table></body></html>';
    }
    private function summary(array$counts):string
    {
        $cells='';
        foreach(self::SUMMARY_LABELS as$key=>$label){
            $count=$counts[$key];
            $cells.='<td width="20%" align="center" valign="top" data-summary-key="'.$key.'" data-summary-count="'.$count.'" '
                .'style="width:20%;padding:4px 1px;background-color:#eef0f4;color:#0b1623;">'
                .'<strong style="display:block;font-size:17px;line-height:1.1;">'.$count.'</strong>'
                .'<span style="font-size:11px;line-height:1.15;">'.$label.'</span></td>';
        }
        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" data-report-summary="counts" '
            .'style="width:100%;table-layout:fixed;border-collapse:collapse;margin:0 0 6px;"><tr>'.$cells.'</tr></table>';
    }
    private function line(string$key,array$r):string
    {
        $base=$r['registrationNumber'].' · '.$r['address'];
        if($key==='progress'){
            $detail=$r['delta']===null
                ? 'Недостаточно данных для оценки'
                : 'Работы '.$r['workStart'].'→'.$r['workEnd'].'/85 · '
                    .'Документы '.$r['documentsStart'].'→'.$r['documentsEnd'].'/15 · '
                    .sprintf('%+d',$r['delta']).' п.п.';
            return$base.' · '.$detail;
        }
        if($key==='overdue')return$base.' · '.$r['label'].' · '.$r['daysLate'].' дн.';
        if($key==='attention')return$base.' · '.$r['label'].' · '.implode(' · ',array_map($this->compactReason(...),$r['reasons']));
        if($key==='plannedClosings'){
            $readiness=$r['total']===null
                ? 'Недостаточно данных для оценки'
                : 'Работы '.$r['work'].'/85 · Документы '.$r['documents'].'/15 · '.$r['total'].'%';
            return$base.' · '.$r['date'].' · '.$r['label'].' · '.$readiness;
        }
        return$base.' · '.$r['date'].' · '.$r['label'];
    }
    private function rowHtml(string$key,array$r):string
    {
        $detail=$this->line($key,$r);
        $suffix=substr($detail,strlen($r['registrationNumber'])+strlen(' · '));
        return '<div data-object-id="'.$this->esc($r['objectId']).'">'
            .'<a href="'.$this->esc($r['url']).'" style="display:inline;padding:0">'
            .$this->esc($r['registrationNumber']).'</a> · '.$this->esc($suffix).'</div>';
    }
    private function compactReason(string$reason):string
    {
        return preg_replace('/^(Работы|Документы): (\d+) из (\d+)$/u','$1 $2/$3',$reason)??$reason;
    }
    private function cell(string$content,string$background='#ffffff'):string
    {
        $style=$background==='#ffffff'?'padding:1px 3px;font-size:13px;line-height:1.25':'padding:3px 5px;background-color:#0b1623;color:#fff';
        return '<tr><td width="100%" align="left" valign="top" style="'.$style.'">'
            .$content.'</td></tr>';
    }
    private function esc(string$value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
