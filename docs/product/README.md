# Product documentation

One folder per front end, **one file per page**. A page's file describes what that page is for,
what it shows, and the rules it enforces — because a rule is easiest to find where someone meets
it.

```
website/   what people use          login · register · tasks · hydration · weight
admin/     the back-office          login · dashboard · accounts · priorities · categories
                                    hydration-presets
```

A surface that lives inside another page but carries its own rules — the hydration and weight
widgets, on the quest log — gets its own file all the same. Burying it in its host page would
hide it from whoever goes looking for it.

A tracker with no reference data has no back-office page, and that is not an omission: weight is
a number the person records and nothing an administrator configures.

Three rules hold everywhere and are therefore written here rather than repeated on every page.

**The API answers in error codes, never in sentences.** `email_already_used`,
`password_too_short`, `category_in_use`: snake_case, one per field that was rejected, all of them
at once rather than the first one. Each front end words them its own way — the website warmly,
the back-office tersely — from a single file per front. Nothing else in either app writes an
error message.

**Someone else's data is answered as *not found*, never as *forbidden*.** A task, a personal
category or an account belonging to another person does not come back with a refusal that
confirms it exists: its very existence is none of the asker's business. The one exception is
something the person *can* see but may not change — a common category, for instance — which
answers *not editable*, because denying what is on their screen would only puzzle them.

**Paginated lists answer with one envelope**: `items`, `total` (matching rows, ignoring
pagination), `page`, `perPage`. Twenty-five rows by default, a hundred at most. A list that is
not paginated says so on its page, with why.
