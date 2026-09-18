# Website · Sleep

A widget on the quest log, beside hydration and weight. Two times and, if one feels like it, a
face: that is the whole of it.

## Which day a night belongs to

**The day you woke up.** A night crosses midnight, so it has to be attached to one of the two
days it touches, and the waking day is the one a person means: "the night of the 18th" is the
one they got up from on the 18th, even though they went to bed on the 17th at 23:30.

Everything else follows from that choice. One night per day. The night of the day in progress is
the only one that can be written, which is the same rule as hydration and weight.

## The widget

**Nothing until this morning's night is noted** — not the last known one. Sleep is not a level
that persists the way a weight is: yesterday's seven hours say nothing about how someone is
today, and showing them would be showing a number that has stopped being true.

So the widget holds one of two things:

- nothing noted yet, and the way to note it;
- the night's **duration** and the **face** that was picked, if one was.

## The window

Opened from the widget: the time one went to bed, the time one got up, and the mood on waking.

**Two times, no dates.** `23:30` and `07:00`, and the server works out the rest: a bedtime later
in the day than the wake-up time means the evening before. Someone who has just woken up should
not have to pick a date, and there is only one date they could mean.

Noting it again corrects it. There is nothing to delete — an unslept night is said by noting
nothing.

## The mood

**A face, from 1 to 5**, 1 being a bad morning and 5 a good one. It is the mood **on waking**,
not the mood of the day that follows: what is being tracked is what the night did to the person.

**It is optional.** Noting one's hours without rating oneself is a real thing to want, and a
tracker that refuses to record until it has been given a feeling is a tracker people stop
opening.

**The scale is fixed, in code.** It is a scale, not reference data: it will not move, and there
is nothing for an administrator to configure. The API answers a number from 1 to 5, and each
front end draws its own face — the same division as the hydration icons. So the back-office has
no sleep page, exactly as it has no weight page.

## The rules

- **One night per day**, the day being the one you woke up on.
- **The current day, and nothing else.** Last night can be noted this morning; the night before
  cannot be noted today. What is missed is missed.
- **Correctable, never deletable.**
- **Between 30 minutes and 16 hours.** A typo filter, not a judgement. `07:30` typed in the
  bedtime field instead of `19:30` is later in the day than the wake-up time, so the rule above
  reads it as the morning before and the night comes out twenty-three hours long — which is how
  that mistake is caught. The same role as the 20–400 kg bounds on weight.
- **A day starts at midnight in `Europe/Paris`**, for everyone — the same placeholder as the
  other two trackers, and the same decision to reopen the day lisar has users elsewhere.
- **The two moments are stored as instants**, not as times of day: `23:30` on the 17th and
  `07:00` on the 18th, each one unambiguous. The duration is computed from them and never
  stored, because a stored duration is a second version of the truth waiting to disagree with
  the first.

## Not in v1

- **naps.** One night per day; a second sleep in the same day has nowhere to go yet.
- **any history**: the list of past nights, the average duration, the mood over a week. All of it
  is being recorded and none of it is shown — the statistics domain will read it.
- **a duration goal**, and everything that follows from it.
- reminders, and anything that watches the phone to guess when someone fell asleep.

## Midnight, with the page still open

Nothing breaks: **no request names a day**, so a page opened yesterday notes to today. And the
widget shows the current day's night or nothing, so at midnight it correctly goes back to
showing nothing — the night of the new day has not been noted yet.

The page reloads all the same, once, for every tracker on it — see `hydration.md`.
