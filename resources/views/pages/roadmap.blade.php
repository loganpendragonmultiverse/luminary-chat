@extends('layouts.app', ['title' => 'Change Log | Luminary Chat', 'description' => 'Luminary release history.'])
@section('content')
<main class="stack-layout changelog-page">
<section class="panel"><p class="eyebrow">Change Log</p><h1>What changed</h1><p class="panel-copy">The updates to Luminary, from the first release to the current version.</p></section>
<section class="panel"><div class="roadmap-stage-meta"><span class="tiny-pill">Version 2.0</span><time datetime="2026-10-12">October 12, 2026</time></div><h2>Three companions, two ways to spend time together</h2><ul class="release-list">
<li>Aurora joins Luminary and Mira. She loves books and imagined adventures, has a dry sense of humor, and grows warmer as she gets to know you. This version uses our adult, platinum-haired design.</li>
<li>Companion Mode now has a furnished one-bedroom home, with a living room, kitchen, dining area, study, bathroom and terrace. We kept a separate copy of the larger house.</li>
<li>All three companions have three complete appearances: swimwear, casual clothes and a luxury dress. The same full-quality models from the character preview are used in the home.</li>
<li>Each person has their own chats, memories and saved home state. Luminary, Mira and Aurora also keep separate histories within that account.</li>
<li>Chat Mode and Companion Mode use the same character personality, enabled memories and selected conversation history. You can continue talking in either place.</li>
<li>The home has its own chat panel. Replies can tell the character to walk to a room, sit in a particular chair, lie on the bed, stand up or stop. She can also decline a request.</li>
<li>Local routines let her explore, sit and rest between replies. These routines, offline catch-up and manual-control reactions do not call an AI model or spend provider credits.</li>
<li>Switch between AI Control and Manual Control. Manual mode supports keyboard movement and furniture clicks. Each character has her own reactions to being moved and can briefly pull against your direction.</li>
<li>Walking, running, sitting, lying down and getting up work with the existing character skeletons. Walking now continues through route waypoints and blends into standing, avoiding repeated animation restarts.</li>
<li>Companion Mode remembers your last character and her appearance and home state. A first visit starts with Luminary. Login is required, and signing in opens character selection.</li>
<li>Aion 3.5 is the recommended starting model for all three characters in both modes. If both keys are available, Venice is preferred; with only OpenRouter, its Aion 3.5 is used. You can change the model at any time.</li>
<li>Home, Chat Mode, Companion Mode and Change Log now share the same navigation, with a clear current-page highlight. Both chat screens have the LIVE Feedback button and matching colors; menus adapt to smaller screens.</li>
<li>Enter sends a message. Shift+Enter adds a new line in both modes.</li>
<li>The homepage is shorter, with four features that match what the app supports: editable memory, distinct personalities, two connected modes and model choice. Guides and support links are grouped together.</li>
<li>The roadmap has been replaced by this change log. The original models and animations are preserved, with private backups for the changes.</li>
</ul></section>
<section class="panel"><div class="roadmap-stage-meta"><span class="tiny-pill">Version 1.4</span><time datetime="2026-07-17">July 17, 2026</time></div><h2>Chat fixes and memory improvements</h2><ul class="release-list"><li>Fixed several bugs.</li><li>Optimized chat memory.</li></ul></section>
<section class="panel"><div class="roadmap-stage-meta"><span class="tiny-pill">Version 1.0</span><time datetime="2026-06-13">June 13, 2026</time></div><h2>Initial release</h2><p class="panel-copy">The first release let you create an account, choose a companion and keep your conversations in one place.</p><ul class="release-list">
<li>User accounts, registration, sign-in and profile settings.</li>
<li>Luminary and Mira as distinct companions, each with her own personality and continuity.</li>
<li>Character profile and persona customization.</li>
<li>Saved conversation threads that could be reopened and continued across sessions.</li>
<li>Bring-your-own Venice and OpenRouter API keys, stored encrypted in the account.</li>
<li>Model selection and switching through the models available to each user’s keys.</li>
<li>Character-specific rooms and situational context for conversations.</li>
<li>Rolling conversation summaries to support continuity in longer chats.</li>
<li>Editable and pinned memories for ongoing preferences, story details and relationship context.</li>
<li>Separate histories, rooms and memories for each character.</li>
<li>Memory and privacy settings controlling what the account retained and shared.</li>
<li>Conversation and account-data export tools.</li>
<li>Account deletion and controls for removing saved data.</li>
</ul></section>
</main>
@endsection
