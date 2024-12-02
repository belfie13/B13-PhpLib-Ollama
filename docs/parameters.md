 # LLM Parameters Reference Table

| Parameter | Description |
|----------- |-------------|
| **Primary Parameters** |
| `temperature` | Controls randomness of outputs (0 = deterministic, higher = more random). Range: 0-5 |
| `top_p` | Selects tokens with probabilities adding up to this number. Higher = more random results. Default: 0.9 |
| `min_p` | Discards tokens with probability smaller than this value × probability of most likely token. Default: 0.1 |
| `top_k` | Selects only top K most likely tokens. Higher = more possible results. Default: 40 |
| **Penalty Samplers** |
| `repeat_last_n` | Number of tokens to consider for penalties. Critical for preventing repetition. Default: 64 (Class 3/4 - but see notes) |
| `repeat_penalty` | Penalizes repeated token sequences. Range: 1.0-1.15. Default: 1.0 |
| `presence_penalty` | Penalizes token presence in previous text. Range: 0-0.2 for Class 3, 0.1-0.35 for Class 4 |
| `frequency_penalty` | Penalizes token frequency in previous text. Range: 0-0.25 for Class 3, 0.4-0.8 for Class 4 |
| `penalize_nl` | Penalizes newline tokens. Generally unused. Default: false |
| **Secondary Samplers** |
| `mirostat` | Controls perplexity during sampling. Modes: 0 (off), 1, or 2 |
| `mirostat_lr` | Mirostat learning rate. Default: 0.1 |
| `mirostat_ent` | Mirostat target entropy. Default: 5.0 |
| `dynatemp_range` | Range for dynamic temperature adjustment. Default: 0.0 |
| `dynatemp_exp` | Exponent for dynamic temperature scaling. Default: 1.0 |
| `tfs` | Tail free sampling - removes low-probability tokens. Default: 1.0 |
| `typical` | Selects tokens more likely than random given prior text. Default: 1.0 |
| `xtc_probability` | Probability of token removal. Range: 0-1 |
| `xtc_threshold` | Threshold for considering token removal. Default: 0.1 |
| **Advanced Samplers** |
| `dry_multiplier` | Controls DRY (Don't Repeat Yourself) intensity. Range: 0.8-1.12+ Class 3 (Class 4 is higher) |
| `dry_allowed_length` | Allowed length for repeated sequences in DRY. Default: 2 |
| `dry_base` | Base value for DRY calculations. Range: 1.15-1.75+ for Class 4 |
| `smoothing_factor` | Quadratic sampling intensity. Range: 1-3 for Class 3, 3-5+ for Class 4 |
| `smoothing_curve` | Quadratic sampling curve. Range: 1 for Class 3, 1.5-2 for Class 4 |
