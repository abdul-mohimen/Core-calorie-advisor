/* ============ CORE CALORIE ADVISOR — per-exercise coaching + auto kit ============
   Before this file the coach spoke from WORKOUT_STATES, which is keyed by the 14
   ANIMATION modes (squat, plank, curl…). Fourteen different exercises therefore
   shared one line: "Russian Twists", "Bicycle Crunches" and "Crunches" all said
   the crunch cue, and every squat-family move (Squats, Lunges, Burpees, Wall Sit,
   Squat Jumps) said the same thing. This keys the cues to the ACTUAL exercise
   name from the database, so the coaching matches the movement being performed.

   Exercise names are the 32 distinct values in the `exercises` table; anim_mode
   is the fallback for anything added later. Coaching content is real form advice
   for each movement, not filler.                                             */
(function (w) {
  'use strict';

  /* setup = said while getting ready · go = said on the switch into the rep
     · push = mid-set encouragement specific to the movement */
  var EX = {
    'squats':               { setup: 'Feet shoulder-width, toes slightly out, chest proud.', go: 'Sit back into the hips, knees tracking over your toes. Depth before speed.', push: 'Drive through your heels — stand tall at the top.' },
    'squat jumps':          { setup: 'Soft knees, weight in the mid-foot.', go: 'Explode up, then land quiet and absorb through the hips.', push: 'Light on the landing — protect those knees.' },
    'lunges':               { setup: 'Long stride, torso upright.', go: 'Drop the back knee straight down. Front shin stays vertical.', push: 'Push through the front heel — control the way down.' },
    'burpees':              { setup: 'Clear some space around you.', go: 'Chest to floor, snap the feet in, then jump tall.', push: 'Set a rhythm you can hold — smooth beats frantic.' },
    'wall sit':             { setup: 'Back flat on the wall, thighs parallel to the floor.', go: 'Hold that ninety-degree angle. Weight in the heels.', push: 'Breathe steady — do not hold your breath.' },
    'push-ups':             { setup: 'Hands under the shoulders, body in one straight line.', go: 'Elbows about forty-five degrees, chest to the floor, full lockout.', push: 'Squeeze your glutes — no sagging hips.' },
    'wide push-ups':        { setup: 'Hands wider than the shoulders.', go: 'Wider grip hits more chest. Lower under control.', push: 'Keep the core braced — one straight line.' },
    'wall push-ups':        { setup: 'Stand an arm-length from the wall.', go: 'Same straight line as a floor push-up, just standing.', push: 'Slow on the way in — that is where the work is.' },
    'plank':                { setup: 'Elbows under the shoulders, feet hip-width.', go: 'Ribs down, glutes tight, one straight line from head to heels.', push: 'Do not let the hips drop — brace like you are about to be punched.' },
    'side plank':           { setup: 'Elbow under the shoulder, feet stacked.', go: 'Lift the hips high and hold. Body in one flat plane.', push: 'Reach the top arm to the ceiling — stay square.' },
    'superman hold':        { setup: 'Face down, arms extended overhead.', go: 'Lift chest, arms and legs together. Squeeze the lower back.', push: 'Look at the floor — keep the neck long.' },
    'glute bridge hold':    { setup: 'On your back, knees bent, feet flat.', go: 'Drive the hips up and squeeze the glutes hard at the top.', push: 'Ribs down — do not arch through the lower back.' },
    'crunches':             { setup: 'Knees bent, hands light behind the ears.', go: 'Curl the ribs toward the hips. Chin off the chest.', push: 'Slow on the way down — that is the half that counts.' },
    'bicycle crunches':     { setup: 'Hands behind the ears, legs off the floor.', go: 'Opposite elbow to opposite knee, rotating through the ribs.', push: 'Slow and deliberate — do not yank on your neck.' },
    'reverse crunch':       { setup: 'On your back, arms flat beside you.', go: 'Curl the knees toward the chest, lifting the hips off the floor.', push: 'Control the lower back down — no swinging.' },
    'leg raises':           { setup: 'Legs straight, hands under the hips for support.', go: 'Lower the legs slowly, keeping the lower back pressed down.', push: 'Stop before the back arches — that is your range.' },
    'seated leg extensions':{ setup: 'Sit tall, hands beside the hips.', go: 'Extend the legs and squeeze the quads at the top.', push: 'Keep the chest lifted — do not round the back.' },
    'russian twists':       { setup: 'Sit back to forty-five degrees, feet light.', go: 'Rotate through the ribcage, not just the arms. Touch each side.', push: 'Chest stays open — control every turn.' },
    'mountain climbers':    { setup: 'Strong push-up position.', go: 'Drive the knees to the chest fast, hips stay low and level.', push: 'Shoulders stacked over the wrists — do not bounce the hips.' },
    'high knees':           { setup: 'Stand tall, core braced.', go: 'Knees to hip height, quick feet, arms driving.', push: 'Stay on the balls of your feet — light and fast.' },
    'jumping jacks':        { setup: 'Feet together, arms at your sides.', go: 'Full range — arms all the way overhead, land soft.', push: 'Find a rhythm and hold it. Breathe.' },
    'bicep curls':          { setup: 'Elbows pinned to your sides.', go: 'Curl without swinging. Squeeze hard at the top.', push: 'Lower slowly — three seconds down.' },
    'bent-over rows':       { setup: 'Hinge at the hips, flat back, chest up.', go: 'Pull the elbows past the ribs, squeeze the shoulder blades.', push: 'Back stays flat — no rounding.' },
    'shoulder press':       { setup: 'Core braced, ribs down.', go: 'Press straight overhead, finish with the biceps by the ears.', push: 'Do not arch the lower back — brace hard.' },
    'squat press':          { setup: 'Feet shoulder-width, weight at the shoulders.', go: 'Squat down, then drive up and press overhead in one flow.', push: 'Let the legs start it — the press finishes it.' },
    'warm up march':        { setup: 'Easy pace, stay relaxed.', go: 'March tall, lifting the knees and swinging the arms.', push: 'Loosen the shoulders — we are just waking up.' },
    'warmup':               { setup: 'Easy pace to start.', go: 'Loosen up and get the blood moving. Nice and controlled.', push: 'Stay relaxed — the hard work comes later.' },
    'child pose':           { setup: 'Knees wide, big toes together.', go: 'Sink the hips back to the heels and reach the arms long.', push: 'Let the breath do the work — soften the shoulders.' },
    'tree pose':            { setup: 'Find a fixed point to look at.', go: 'Foot to the calf or thigh, never the knee. Grow tall.', push: 'Wobbling is normal — breathe and reset.' },
    'warrior hold':         { setup: 'Long stance, front knee over the ankle.', go: 'Sink into the front leg, arms strong, gaze forward.', push: 'Shoulders down, back leg straight and powerful.' },
    'deep breathing':       { setup: 'Sit or stand comfortably.', go: 'In through the nose for four, out through the mouth for six.', push: 'Let the shoulders drop. Slow it right down.' }
  };

  /* Fallback by animation mode when an exercise name is not in the table. */
  var MODE = {
    squat:      { setup: 'Feet shoulder-width, chest up.', go: 'Sit back into the hips and drive through the heels.', push: 'Depth before speed.' },
    pushup:     { setup: 'Hands under the shoulders.', go: 'One straight line, chest to the floor, full lockout.', push: 'Squeeze the glutes — no sagging.' },
    plank:      { setup: 'Elbows under the shoulders.', go: 'Brace hard and hold one straight line.', push: 'Do not let the hips drop.' },
    crunch:     { setup: 'Knees bent, chin off the chest.', go: 'Curl the ribs toward the hips.', push: 'Slow on the way down.' },
    legraise:   { setup: 'Lower back pressed down.', go: 'Lower the legs under control.', push: 'Stop before the back arches.' },
    twist:      { setup: 'Sit back, chest open.', go: 'Rotate through the ribcage.', push: 'Control every turn.' },
    curl:       { setup: 'Elbows pinned in.', go: 'Curl without swinging, squeeze at the top.', push: 'Three seconds down.' },
    press:      { setup: 'Ribs down, core braced.', go: 'Press straight overhead.', push: 'Do not arch the lower back.' },
    highknees:  { setup: 'Stand tall.', go: 'Knees to hip height, quick feet.', push: 'Light and fast.' },
    jumpingjack:{ setup: 'Feet together.', go: 'Full range, land soft.', push: 'Find your rhythm.' },
    mountain:   { setup: 'Strong plank position.', go: 'Drive the knees in, hips stay low.', push: 'Do not bounce the hips.' },
    wallpushup: { setup: 'Arm-length from the wall.', go: 'Straight line, lower under control.', push: 'Slow on the way in.' },
    warmup:     { setup: 'Easy pace.', go: 'Loosen up and get the blood moving.', push: 'Stay relaxed.' },
    yoga:       { setup: 'Settle your breathing.', go: 'Move slowly into the shape and hold.', push: 'Let the breath lead.' },
    idle:       { setup: 'Ready when you are.', go: 'Keep your form tight and drive through each rep.', push: 'Stay strong.' }
  };

  function norm(s) { return String(s || '').trim().toLowerCase(); }

  function cues(exerciseName, animMode) {
    return EX[norm(exerciseName)] || MODE[norm(animMode)] || MODE.idle;
  }

  /* ---- Automatic kit per workout category ----------------------------------
     The visitor never picks this; it follows the workout they opened. Values
     are the textures that ship in assets/models/outfits/. An empty string means
     the graphite kit already baked into trainers.glb (no extra download). */
  /* Two trainers ship with different UV layouts, so each needs its OWN kit
     textures — feeding MocapGuy's atlas to Ch06 would smear the wrong pixels
     over the wrong body parts. `which` selects the set:
       'ch06'      → the default trainer (tracksuit + trainers + hair)
       'mocapguy'  → the fallback trainer (compression kit) */
  var KITS = {
    ch06: {
      'strength':        '',                                   // navy tracksuit, already in the GLB
      'hiit-cardio':     'assets/models/outfits/ch06-ember.png',
      'yoga-stretching': 'assets/models/outfits/ch06-forest.png',
      'warmup-recovery': 'assets/models/outfits/ch06-ocean.png'
    },
    mocapguy: {
      'strength':        '',                                   // graphite, already in the GLB
      'hiit-cardio':     'assets/models/outfits/ember.png',
      'yoga-stretching': 'assets/models/outfits/forest.png',
      'warmup-recovery': 'assets/models/outfits/ocean.png'
    }
  };
  var MODE_TO_CATEGORY = { yoga: 'yoga-stretching', warmup: 'warmup-recovery' };

  function kitFor(category, animMode, which) {
    var set = KITS[norm(which) === 'mocapguy' ? 'mocapguy' : 'ch06'];
    var c = norm(category);
    if (Object.prototype.hasOwnProperty.call(set, c)) return set[c];
    var mapped = MODE_TO_CATEGORY[norm(animMode)];
    if (mapped && Object.prototype.hasOwnProperty.call(set, mapped)) return set[mapped];
    return '';
  }

  /* Which kit set applies, judged from the model's own material names. */
  function kitSetFor(root) {
    var found = 'ch06';
    if (!root || !root.traverse) return found;
    root.traverse(function (o) {
      if (!o.isMesh && !o.isSkinnedMesh) return;
      var list = Array.isArray(o.material) ? o.material : [o.material];
      list.forEach(function (m) { if (m && m.name === 'Body_MAT') found = 'mocapguy'; });
    });
    return found;
  }

  /* Material names that carry the garment texture, per trainer. */
  var KIT_MATERIALS = ['Ch06_body', 'Ch06_eyelashes', 'Body_MAT'];

  w.CCACoach = {
    cues: cues,
    kitFor: kitFor,
    kitSetFor: kitSetFor,
    isKitMaterial: function (name) { return KIT_MATERIALS.indexOf(name) !== -1; },
    hasExercise: function (n) { return !!EX[norm(n)]; },
    exerciseCount: Object.keys(EX).length
  };
})(window);
