<?php
namespace App\Services;

class CompanionActionService
{
    public const ACTIONS = ['none','living','kitchen','bedroom','terrace','study','dining','bathroom','sit','lie','stand','stop','face'];

    public function decision(string $response): array
    {
        $text=trim(preg_replace('/^```(?:json)?\s*|\s*```$/i','',trim($response)));
        $parsed=json_decode($text,true);
        if (!is_array($parsed) && str_contains($text,'{')) {
            $start=strpos($text,'{'); $end=strrpos($text,'}');
            if ($end!==false) $parsed=json_decode(substr($text,$start,$end-$start+1),true);
        }
        $reply=is_array($parsed)&&is_string($parsed['reply']??null)?trim($parsed['reply']):$text;
        $declared=is_array($parsed)&&is_string($parsed['action']??null)?strtolower(trim($parsed['action'])):'none';
        $declared=str_replace([' ','-'],'_',$declared);
        $declared=['lie_down'=>'lie','lay_down'=>'lie','sit_down'=>'sit','stand_up'=>'stand','living_room'=>'living','dining_room'=>'dining'][$declared]??$declared;
        if (!in_array($declared,self::ACTIONS,true)) $declared='none';
        $narrated=$this->fromReply($reply);
        $action=$declared==='none'?$narrated:$declared;
        if ($action==='bedroom'&&$narrated==='lie') $action='lie';
        if ($this->refusesMovement($reply)) $action='none';
        $targets=['bed','sofa-left','sofa-right','lounge','study-chair','dining-chair','dining-north','dining-west','dining-east','terrace-chair','terrace-right'];
        $target=is_array($parsed)&&is_string($parsed['target']??null)&&in_array($parsed['target'],$targets,true)?$parsed['target']:null;
        if ($action==='lie') $target='bed';
        if ($action==='sit'&&!$target) {
            $matches=[];
            preg_match_all('/\b(study|desk|sofa|couch|lounge|dining|terrace)\b/i',$reply,$matches);
            $place=strtolower((string)end($matches[1]));
            $target=['study'=>'study-chair','desk'=>'study-chair','sofa'=>'sofa-left','couch'=>'sofa-left','lounge'=>'lounge','dining'=>'dining-chair','terrace'=>'terrace-chair'][$place]??null;
        }
        return ['reply'=>$reply,'action'=>$action,'target'=>$target];
    }

    private function refusesMovement(string $text): bool
    {
        return (bool)preg_match('/\b(?:i|she|luminary|mira|aurora)\s+(?:won.t|will\s+not|refuse(?:s)?\s+to|don.t\s+want\s+to|do(?:es)?\s+not\s+want\s+to|doesn.t\s+want\s+to|would\s+rather\s+not)\s+(?:sit|lie|lay|walk|move|go|stand)\b/i',$text);
    }

    public function fromReply(string $reply): string
    {
        // Infer chosen current-scene movement, never a user command alone.
        if ($this->refusesMovement($reply)) return 'none';
        $text=strtolower(str_replace(['*','_','’'],['','','\''],$reply));
        $actor='(?:\b(?:i|she|luminary|mira|aurora)\s+(?:will\s+|am\s+going\s+to\s+|is\s+going\s+to\s+)?|\bi\x27ll\s+|\blet\x27s\s+)';
        if (preg_match('/'.$actor.'[^.!?\n]{0,130}\band\s+(?:lie|lies|lay|lays)\s+down\b/',$text)) return 'lie';
        if (preg_match('/'.$actor.'[^.!?\n]{0,130}\band\s+(?:sit|sits)\s+down\b/',$text)) return 'sit';
        if (preg_match('/'.$actor.'(?:lie|lies|lay|lays|stretch|stretches)\s+(?:down|out|on)\b/',$text)) return 'lie';
        if (preg_match('/'.$actor.'(?:sit|sits|settle|settles)\s+(?:down|in|on|into)\b/',$text)) return 'sit';
        if (preg_match('/'.$actor.'(?:stand|stands|get|gets)\s+up\b/',$text)) return 'stand';
        foreach (['living'=>'living room','kitchen'=>'kitchen','bedroom'=>'bedroom','terrace'=>'terrace','study'=>'study','dining'=>'dining room','bathroom'=>'bathroom'] as $action=>$room) {
            if (preg_match('/'.$actor.'(?:walks?|moves?|go|goes|heads?|steps?|returns?|leads?)\b[^.!?\n]{0,80}\b(?:to|toward|towards|into|inside)\s+(?:the\s+)?'.preg_quote($room,'/').'\b/',$text)) return $action;
        }
        if (preg_match('/\*(?:\s*)(?:sits|settles)\s+(?:down|in|into|on)\b/i',$reply)) return 'sit';
        if (preg_match('/\*(?:\s*)(?:lies|lays)\s+(?:down|on)\b/i',$reply)) return 'lie';
        return 'none';
    }
}
