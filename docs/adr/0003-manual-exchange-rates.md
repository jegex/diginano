# Manual exchange rates and order price snapshots

Prices are stored in USD; display conversion uses a manually-maintained `ExchangeRate` table rather than a live FX API, and every Order snapshots the USD price plus the rate in effect at checkout so later rate changes never alter history. A live FX API was rejected: it adds an external dependency and nondeterministic pricing. Manual rates keep pricing predictable and auditable; settlement always happens in the gateway's own currency regardless of the display currency.
